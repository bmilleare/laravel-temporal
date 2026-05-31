<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\Schedule\Policy\ScheduleOverlapPolicy;
use Temporal\Client\ScheduleClientInterface;

#[AsCommand('temporal:schedule:trigger')]
class ScheduleTriggerCommand extends Command
{
    protected $signature = 'temporal:schedule:trigger
                        {id : The schedule id to trigger}
                        {--overlap= : Overlap policy: Skip, BufferOne, BufferAll, CancelOther, TerminateOther, AllowAll}';

    protected $description = 'Trigger an immediate run of a Temporal schedule';

    public function handle(ScheduleClientInterface $client): int
    {
        $id = (string) $this->argument('id');
        if ($id === '') {
            $this->error('A schedule id is required.');

            return self::FAILURE;
        }

        $client->getHandle($id)->trigger($this->overlapPolicy());

        $this->info(sprintf('Triggered schedule [%s].', $id));

        return self::SUCCESS;
    }

    protected function overlapPolicy(): ScheduleOverlapPolicy
    {
        return match (strtolower((string) $this->option('overlap'))) {
            'skip' => ScheduleOverlapPolicy::Skip,
            'bufferone' => ScheduleOverlapPolicy::BufferOne,
            'bufferall' => ScheduleOverlapPolicy::BufferAll,
            'cancelother' => ScheduleOverlapPolicy::CancelOther,
            'terminateother' => ScheduleOverlapPolicy::TerminateOther,
            'allowall' => ScheduleOverlapPolicy::AllowAll,
            default => ScheduleOverlapPolicy::Unspecified,
        };
    }
}
