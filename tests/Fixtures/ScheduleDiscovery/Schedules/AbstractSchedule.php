<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules;

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;

/**
 * An abstract definition living alongside the concrete ones to prove discovery
 * skips non-instantiable classes even when they implement ScheduleDefinition.
 */
abstract class AbstractSchedule implements ScheduleDefinition
{
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder
    {
        return $schedule->id('abstract')->cron('0 0 * * *');
    }
}
