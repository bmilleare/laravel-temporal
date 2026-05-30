<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules;

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;

class DemoSchedule implements ScheduleDefinition
{
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder
    {
        return $schedule
            ->id('demo-schedule')
            ->cron('* * * * *')
            ->startWorkflow(DemoWorkflowInterface::class);
    }
}
