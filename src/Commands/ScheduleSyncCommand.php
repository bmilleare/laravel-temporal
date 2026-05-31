<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Commands\Concerns\HandlesScheduleApiSupport;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;
use Keepsuit\LaravelTemporal\Support\ScheduleHasher;
use Keepsuit\LaravelTemporal\Support\ScheduleMemo;
use Keepsuit\LaravelTemporal\Support\ScheduleReconciler;
use Keepsuit\LaravelTemporal\Support\ScheduleReconciliationPlan;
use Keepsuit\LaravelTemporal\TemporalRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleOptions;
use Temporal\Client\ScheduleClientInterface;
use Temporal\Exception\Client\ServiceClientException;

#[AsCommand('temporal:schedule:sync')]
class ScheduleSyncCommand extends Command
{
    use HandlesScheduleApiSupport;

    protected $signature = 'temporal:schedule:sync
                        {--prune : Delete managed schedules that no longer have a matching definition}
                        {--dry-run : Show the planned changes without applying them}';

    protected $description = 'Synchronize declared Temporal schedule definitions with the server';

    public function handle(ScheduleClientInterface $client, TemporalRegistry $registry): int
    {
        try {
            $desired = $this->desiredSchedules($registry);
        } catch (InvalidArgumentException $invalidArgumentException) {
            $this->error($invalidArgumentException->getMessage());

            return self::FAILURE;
        }

        try {
            $existing = $this->existingSchedules($client);
        } catch (ServiceClientException $serviceClientException) {
            if ($this->reportUnsupportedSchedulesApi($serviceClientException)) {
                return self::FAILURE;
            }

            throw $serviceClientException;
        }

        $plan = ScheduleReconciler::plan(
            array_map(fn (array $schedule) => $schedule['hash'], $desired),
            $existing,
        );

        if ($this->option('dry-run')) {
            $this->renderPlan($plan, applied: false);

            return self::SUCCESS;
        }

        foreach ($plan->create as $id) {
            $client->createSchedule($desired[$id]['schedule'], $desired[$id]['options'], $id);
        }

        // The SDK's update() cannot rewrite a schedule's memo (Temporal's
        // UpdateSchedule RPC carries no memo field), so the drift hash stored
        // there would never refresh and every later sync would re-update the
        // same schedule forever. Delete + recreate restamps the memo with the
        // new hash and applies the full declared spec, memo and search
        // attributes in one shot — the declaration is the source of truth.
        foreach ($plan->update as $id) {
            $client->getHandle($id)->delete();
            $client->createSchedule($desired[$id]['schedule'], $desired[$id]['options'], $id);
        }

        if ($this->option('prune')) {
            foreach ($plan->prunable as $id) {
                $client->getHandle($id)->delete();
            }
        }

        $this->renderPlan($plan, applied: true);

        return self::SUCCESS;
    }

    /**
     * @return array<non-empty-string, array{schedule: Schedule, options: ScheduleOptions, hash: string}>
     */
    protected function desiredSchedules(TemporalRegistry $registry): array
    {
        $desired = [];
        $declaredBy = [];

        foreach ($registry->schedules() as $definitionClass) {
            $definition = $this->laravel->make($definitionClass);
            if (! $definition instanceof ScheduleDefinition) {
                continue;
            }

            $builder = $definition->configure(ScheduleBuilder::new());
            $id = $builder->scheduleId() ?? Str::kebab(class_basename($definitionClass));
            if ($id === '') {
                continue;
            }

            if (isset($declaredBy[$id])) {
                throw new InvalidArgumentException(sprintf(
                    'Duplicate schedule id [%s] is declared by both [%s] and [%s].',
                    $id, $declaredBy[$id], $definitionClass,
                ));
            }

            $declaredBy[$id] = $definitionClass;

            $schedule = $builder->build();
            $options = $builder->scheduleOptions();

            // Filter the declarative memo: drop the package's reserved marker
            // keys (warning the operator rather than silently honouring them,
            // which would corrupt drift detection) and ignore non-string keys.
            $userMemo = [];
            foreach ($options->memo->getValues() as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }

                if ($key === ScheduleMemo::MANAGED_KEY || $key === ScheduleMemo::HASH_KEY) {
                    $this->warn(sprintf('Schedule [%s] memo key [%s] is reserved by this package and was ignored.', $id, $key));

                    continue;
                }

                $userMemo[$key] = $value;
            }

            // The hash covers the declarative memo and search attributes too, so
            // changing either is detected as drift and reconciled (via recreate).
            $hash = ScheduleHasher::hash($schedule, $userMemo, $options->searchAttributes->getValues());

            $options = $options->withMemo(ScheduleMemo::markers($hash) + $userMemo);

            $desired[$id] = ['schedule' => $schedule, 'options' => $options, 'hash' => $hash];
        }

        return $desired;
    }

    /**
     * @return array<non-empty-string, array{hash: ?string, managed: bool}>
     */
    protected function existingSchedules(ScheduleClientInterface $client): array
    {
        $existing = [];

        foreach ($client->listSchedules() as $entry) {
            $id = $entry->scheduleId;
            if ($id === '') {
                continue;
            }

            $existing[$id] = [
                'hash' => ScheduleMemo::hash($entry->memo),
                'managed' => ScheduleMemo::isManaged($entry->memo),
            ];
        }

        return $existing;
    }

    protected function renderPlan(ScheduleReconciliationPlan $plan, bool $applied): void
    {
        $rows = [
            ...array_map(fn (string $id) => [$id, $applied ? 'created' : 'create'], $plan->create),
            ...array_map(fn (string $id) => [$id, $applied ? 'updated' : 'update'], $plan->update),
            ...array_map(fn (string $id) => [$id, 'unchanged'], $plan->unchanged),
        ];

        if ($this->option('prune')) {
            $rows = [
                ...$rows,
                ...array_map(fn (string $id) => [$id, $applied ? 'pruned' : 'prune'], $plan->prunable),
            ];
        } elseif ($plan->prunable !== []) {
            $this->warn(sprintf('%d managed schedule(s) have no definition. Run with --prune to remove them.', count($plan->prunable)));
        }

        $rows = [
            ...$rows,
            ...array_map(fn (string $id) => [$id, 'conflict (unmanaged)'], $plan->conflicts),
        ];

        foreach ($plan->conflicts as $id) {
            $this->warn(sprintf('Schedule [%s] already exists but is not managed by this package; leaving it untouched. Rename the definition or adopt the schedule.', $id));
        }

        if ($rows === []) {
            $this->info('Schedules already in sync.');

            return;
        }

        $this->table(['Schedule', 'Action'], $rows);
    }
}
