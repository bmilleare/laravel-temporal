<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
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
        $desired = $this->desiredSchedules($registry);

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

        foreach ($plan->update as $id) {
            $client->getHandle($id)->update($desired[$id]['schedule']);
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

            $schedule = $builder->build();
            $hash = ScheduleHasher::hash($schedule);

            $options = $builder->scheduleOptions();

            // Carry the user's memo through, then stamp the ownership + hash
            // markers. The package's markers always win — a user memo that
            // reuses a reserved key would break drift detection, so it is
            // dropped and the operator is warned rather than silently ignored.
            $memo = ScheduleMemo::markers($hash);
            $reserved = array_keys($memo);
            foreach ($options->memo->getValues() as $key => $value) {
                if (! is_string($key) || $key === '') {
                    continue;
                }

                if (in_array($key, $reserved, true)) {
                    $this->warn(sprintf('Schedule [%s] memo key [%s] is reserved by this package and was ignored.', $id, $key));

                    continue;
                }

                $memo[$key] = $value;
            }

            $options = $options->withMemo($memo);

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

        if ($rows === []) {
            $this->info('Schedules already in sync.');

            return;
        }

        $this->table(['Schedule', 'Action'], $rows);
    }
}
