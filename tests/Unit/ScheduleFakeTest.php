<?php

use Keepsuit\LaravelTemporal\Facade\Temporal;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;
use Temporal\Client\Schedule\Schedule;

it('records and asserts schedules created while faked', function () {
    Temporal::fake();

    $schedule = Temporal::newSchedule()
        ->id('daily-report')
        ->cron('0 9 * * *')
        ->startWorkflow(DemoWorkflowInterface::class)
        ->build();

    Temporal::scheduleClient()->createSchedule($schedule, null, 'daily-report');

    Temporal::assertScheduleCreated('daily-report');
    Temporal::assertScheduleNotCreated('weekly-report');
});

it('asserts a created schedule against a callback', function () {
    Temporal::fake();

    $schedule = Temporal::newSchedule()->id('hourly')->cron('0 * * * *')->build();
    Temporal::scheduleClient()->createSchedule($schedule, null, 'hourly');

    Temporal::assertScheduleCreated(callback: fn (Schedule $created) => $created->spec->cronStringList === ['0 * * * *']);
});
