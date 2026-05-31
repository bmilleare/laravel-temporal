<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Keepsuit\LaravelTemporal\Support\ScheduleMemo;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\ScheduleClientInterface;

#[AsCommand('temporal:schedule:list')]
class ScheduleListCommand extends Command
{
    protected $signature = 'temporal:schedule:list
                        {--managed : Only show schedules managed by this application}';

    protected $description = 'List the Temporal schedules registered on the server';

    public function handle(ScheduleClientInterface $client): int
    {
        $rows = [];

        foreach ($client->listSchedules() as $entry) {
            $managed = ScheduleMemo::isManaged($entry->memo);

            if ($this->option('managed') && ! $managed) {
                continue;
            }

            $rows[] = [
                $entry->scheduleId,
                $entry->info->paused ? 'paused' : 'active',
                $managed ? 'yes' : 'no',
            ];
        }

        if ($rows === []) {
            $this->info('No schedules found.');

            return self::SUCCESS;
        }

        $this->table(['Schedule', 'State', 'Managed'], $rows);

        return self::SUCCESS;
    }
}
