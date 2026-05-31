<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands;

use InvalidArgumentException;
use Symfony\Component\Console\Attribute\AsCommand;
use Temporal\Client\Schedule\Policy\ScheduleOverlapPolicy;
use Temporal\Client\Schedule\ScheduleHandle;

#[AsCommand('temporal:schedule:trigger')]
class ScheduleTriggerCommand extends ScheduleActionCommand
{
    protected $signature = 'temporal:schedule:trigger
                        {id : The schedule id to trigger}
                        {--overlap= : Overlap policy: Skip, BufferOne, BufferAll, CancelOther, TerminateOther, AllowAll}';

    protected $description = 'Trigger an immediate run of a Temporal schedule';

    protected function performAction(ScheduleHandle $handle, string $id): string
    {
        $handle->trigger($this->overlapPolicy());

        return sprintf('Triggered schedule [%s].', $id);
    }

    protected function overlapPolicy(): ScheduleOverlapPolicy
    {
        $overlap = $this->option('overlap');
        if ($overlap === null || $overlap === '') {
            return ScheduleOverlapPolicy::Unspecified;
        }

        return match (strtolower((string) $overlap)) {
            'skip' => ScheduleOverlapPolicy::Skip,
            'bufferone' => ScheduleOverlapPolicy::BufferOne,
            'bufferall' => ScheduleOverlapPolicy::BufferAll,
            'cancelother' => ScheduleOverlapPolicy::CancelOther,
            'terminateother' => ScheduleOverlapPolicy::TerminateOther,
            'allowall' => ScheduleOverlapPolicy::AllowAll,
            default => throw new InvalidArgumentException(sprintf(
                'Unknown overlap policy [%s]. Allowed: Skip, BufferOne, BufferAll, CancelOther, TerminateOther, AllowAll.',
                (string) $overlap,
            )),
        };
    }
}
