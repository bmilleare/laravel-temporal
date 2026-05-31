<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands;

use Keepsuit\LaravelTemporal\Commands\Concerns\ResolvesScheduleNote;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\Schedule\ScheduleHandle;

#[AsCommand('temporal:schedule:pause')]
class SchedulePauseCommand extends ScheduleActionCommand
{
    use ResolvesScheduleNote;

    protected $signature = 'temporal:schedule:pause
                        {id : The schedule id to pause}
                        {--note= : An informative note stored on the schedule}';

    protected $description = 'Pause a Temporal schedule';

    protected function performAction(ScheduleHandle $handle, string $id): string
    {
        $handle->pause($this->noteOption('Paused via PHP SDK'));

        return sprintf('Paused schedule [%s].', $id);
    }
}
