<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules;

use Carbon\CarbonInterval;
use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;

class ReportSchedule implements ScheduleDefinition
{
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder
    {
        return $schedule
            ->id('report-schedule')
            ->interval(CarbonInterval::hour())
            ->startWorkflow(DemoWorkflowInterface::class);
    }
}
