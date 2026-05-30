<?php

use Keepsuit\LaravelTemporal\Support\DiscoverSchedules;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\DemoSchedule;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\ReportSchedule;

it('discovers schedule definitions and ignores other classes', function () {
    $schedules = DiscoverSchedules::within(
        __DIR__.'/../../Fixtures/ScheduleDiscovery/Schedules',
    );

    expect($schedules)->toBe([
        DemoSchedule::class,
        ReportSchedule::class,
    ]);
});

it('returns an empty array for a missing directory', function () {
    expect(DiscoverSchedules::within(__DIR__.'/does-not-exist'))->toBe([]);
});
