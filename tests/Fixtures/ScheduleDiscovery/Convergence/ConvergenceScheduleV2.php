<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Convergence;

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;

/**
 * Same id as V1 but a different cron — represents an edited definition, used to
 * prove that sync reconciles the change and restamps the drift hash so the next
 * sync converges (the bug that delete+recreate fixes).
 */
class ConvergenceScheduleV2 implements ScheduleDefinition
{
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder
    {
        return $schedule
            ->id('convergence-schedule')
            ->cron('0 0 2 2 *')
            ->paused()
            ->startWorkflow(DemoWorkflowInterface::class);
    }
}
