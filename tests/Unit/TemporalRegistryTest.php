<?php

use Keepsuit\LaravelTemporal\TemporalRegistry;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\DemoSchedule;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\ReportSchedule;

it('registers schedule definitions without duplicates', function () {
    $registry = (new TemporalRegistry)
        ->registerSchedules(DemoSchedule::class, DemoSchedule::class)
        ->registerSchedules(ReportSchedule::class);

    expect(array_values($registry->schedules()))
        ->toBe([DemoSchedule::class, ReportSchedule::class]);

    expect($registry->toArray())->toHaveKey('schedules');
});
