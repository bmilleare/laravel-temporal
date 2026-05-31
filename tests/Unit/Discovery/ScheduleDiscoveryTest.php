<?php

use Keepsuit\LaravelTemporal\Support\DiscoverSchedules;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\AbstractSchedule;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\DemoSchedule;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\ReportSchedule;

it('discovers instantiable definitions and ignores other and non-instantiable classes', function () {
    $schedules = DiscoverSchedules::within(
        __DIR__.'/../../Fixtures/ScheduleDiscovery/Schedules',
    );

    // NotASchedule (no interface) and AbstractSchedule (not instantiable) are excluded.
    expect($schedules)
        ->toBe([
            DemoSchedule::class,
            ReportSchedule::class,
        ])
        ->not->toContain(AbstractSchedule::class);
});

it('returns an empty array for a missing directory', function () {
    expect(DiscoverSchedules::within(__DIR__.'/does-not-exist'))->toBe([]);
});
