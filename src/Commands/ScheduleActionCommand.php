<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use InvalidArgumentException;
use Keepsuit\LaravelTemporal\Commands\Concerns\HandlesScheduleApiSupport;
use Temporal\Client\Schedule\ScheduleHandle;
use Temporal\Client\ScheduleClientInterface;
use Temporal\Exception\Client\ServiceClientException;

/**
 * Shared base for the single-schedule management commands (trigger/pause/
 * unpause). Centralises schedule-id validation, the "Schedules API not
 * available" translation, and friendly handling of the SDK's argument
 * validation so each subcommand only declares its own action.
 */
abstract class ScheduleActionCommand extends Command
{
    use HandlesScheduleApiSupport;

    public function handle(ScheduleClientInterface $client): int
    {
        $id = trim((string) $this->argument('id'));
        if ($id === '') {
            $this->error('A schedule id is required.');

            return self::FAILURE;
        }

        try {
            $message = $this->performAction($client->getHandle($id), $id);
        } catch (ServiceClientException $serviceClientException) {
            if ($this->reportUnsupportedSchedulesApi($serviceClientException)) {
                return self::FAILURE;
            }

            throw $serviceClientException;
        } catch (InvalidArgumentException $invalidArgumentException) {
            // e.g. an invalid --overlap value, or the SDK rejecting an empty note.
            $this->error($invalidArgumentException->getMessage());

            return self::FAILURE;
        }

        $this->info($message);

        return self::SUCCESS;
    }

    /**
     * Perform the command's action against the resolved handle and return the
     * success message to display.
     */
    abstract protected function performAction(ScheduleHandle $handle, string $id): string;
}
