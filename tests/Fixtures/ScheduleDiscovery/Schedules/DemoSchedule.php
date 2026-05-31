<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules;

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;

class DemoSchedule implements ScheduleDefinition
{
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder
    {
        // Yearly cadence + paused so that if an integration test is killed
        // before cleanup, a leaked schedule never actually fires a workflow.
        return $schedule
            ->id('demo-schedule')
            ->cron('0 0 1 1 *')
            ->paused()
            ->startWorkflow(DemoWorkflowInterface::class);
    }
}
