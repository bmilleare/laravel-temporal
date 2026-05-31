<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands;

use Keepsuit\LaravelTemporal\Commands\Concerns\ResolvesScheduleNote;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\Schedule\ScheduleHandle;

#[AsCommand('temporal:schedule:unpause')]
class ScheduleUnpauseCommand extends ScheduleActionCommand
{
    use ResolvesScheduleNote;

    protected $signature = 'temporal:schedule:unpause
                        {id : The schedule id to unpause}
                        {--note= : An informative note stored on the schedule}';

    protected $description = 'Resume a paused Temporal schedule';

    protected function performAction(ScheduleHandle $handle, string $id): string
    {
        $handle->unpause($this->noteOption('Unpaused via PHP SDK'));

        return sprintf('Unpaused schedule [%s].', $id);
    }
}
