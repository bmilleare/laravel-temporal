<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\ScheduleClientInterface;

#[AsCommand('temporal:schedule:pause')]
class SchedulePauseCommand extends Command
{
    protected $signature = 'temporal:schedule:pause
                        {id : The schedule id to pause}
                        {--note= : An informative note stored on the schedule}';

    protected $description = 'Pause a Temporal schedule';

    public function handle(ScheduleClientInterface $client): int
    {
        $id = (string) $this->argument('id');
        if ($id === '') {
            $this->error('A schedule id is required.');

            return self::FAILURE;
        }

        $note = $this->option('note');
        $note = is_string($note) ? $note : 'Paused via PHP SDK';

        $client->getHandle($id)->pause($note);

        $this->info(sprintf('Paused schedule [%s].', $id));

        return self::SUCCESS;
    }
}
