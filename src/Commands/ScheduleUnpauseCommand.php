<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\ScheduleClientInterface;

#[AsCommand('temporal:schedule:unpause')]
class ScheduleUnpauseCommand extends Command
{
    protected $signature = 'temporal:schedule:unpause
                        {id : The schedule id to unpause}
                        {--note= : An informative note stored on the schedule}';

    protected $description = 'Resume a paused Temporal schedule';

    public function handle(ScheduleClientInterface $client): int
    {
        $id = (string) $this->argument('id');
        if ($id === '') {
            $this->error('A schedule id is required.');

            return self::FAILURE;
        }

        $note = $this->option('note');
        $note = is_string($note) ? $note : 'Unpaused via PHP SDK';

        $client->getHandle($id)->unpause($note);

        $this->info(sprintf('Unpaused schedule [%s].', $id));

        return self::SUCCESS;
    }
}
