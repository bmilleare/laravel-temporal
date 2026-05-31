<?php

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Facade\Temporal;
use Keepsuit\LaravelTemporal\Support\ScheduleMemo;
use Keepsuit\LaravelTemporal\TemporalRegistry;
use Keepsuit\LaravelTemporal\Testing\WithTemporal;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules\DemoSchedule;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;
use Temporal\Client\Schedule\ScheduleOptions;

uses(WithTemporal::class);

/**
 * The Temporal CLI dev server may not expose the Schedules API. These tests
 * skip themselves when listSchedules() is unavailable rather than failing.
 */
function temporalSchedulesSupported(): bool
{
    try {
        iterator_to_array(Temporal::scheduleClient()->listSchedules());

        return true;
    } catch (Throwable) {
        return false;
    }
}

function findSchedule(string $id): mixed
{
    foreach (Temporal::scheduleClient()->listSchedules() as $entry) {
        if ($entry->scheduleId === $id) {
            return $entry;
        }
    }

    return null;
}

it('creates a schedule with an ownership memo that round-trips through list', function () {
    $id = 'it-create-'.uniqid();

    $schedule = ScheduleBuilder::new()
        ->id($id)
        ->cron('0 9 * * *')
        ->startWorkflow(DemoWorkflowInterface::class)
        ->build();

    $options = (new ScheduleOptions)->withMemo(ScheduleMemo::markers('hash-1'));

    Temporal::scheduleClient()->createSchedule($schedule, $options, $id);

    try {
        $entry = findSchedule($id);

        expect($entry)->not->toBeNull();
        expect(ScheduleMemo::isManaged($entry->memo))->toBeTrue();
        expect(ScheduleMemo::hash($entry->memo))->toBe('hash-1');
    } finally {
        Temporal::scheduleClient()->getHandle($id)->delete();
    }
})->skip(fn () => ! temporalSchedulesSupported(), 'Temporal test server does not support the Schedules API.');

it('pauses and unpauses a schedule', function () {
    $id = 'it-pause-'.uniqid();

    $schedule = ScheduleBuilder::new()->id($id)->cron('0 9 * * *')->startWorkflow(DemoWorkflowInterface::class)->build();
    Temporal::scheduleClient()->createSchedule($schedule, null, $id);

    try {
        Temporal::scheduleClient()->getHandle($id)->pause('maintenance');
        expect(findSchedule($id)->info->paused)->toBeTrue();

        Temporal::scheduleClient()->getHandle($id)->unpause('resumed');
        expect(findSchedule($id)->info->paused)->toBeFalse();
    } finally {
        Temporal::scheduleClient()->getHandle($id)->delete();
    }
})->skip(fn () => ! temporalSchedulesSupported(), 'Temporal test server does not support the Schedules API.');

it('syncs declared schedule definitions idempotently and prunes managed orphans', function () {
    app()->bind(TemporalRegistry::class, fn () => (new TemporalRegistry)->registerSchedules(DemoSchedule::class));

    try {
        $this->artisan('temporal:schedule:sync')->assertSuccessful();
        expect(findSchedule('demo-schedule'))->not->toBeNull();

        // Re-running with no changes is a no-op.
        $this->artisan('temporal:schedule:sync')->assertSuccessful();

        // Dropping the definition + --prune removes the now-orphaned managed schedule.
        app()->bind(TemporalRegistry::class, fn () => new TemporalRegistry);
        $this->artisan('temporal:schedule:sync', ['--prune' => true])->assertSuccessful();

        expect(findSchedule('demo-schedule'))->toBeNull();
    } finally {
        try {
            Temporal::scheduleClient()->getHandle('demo-schedule')->delete();
        } catch (Throwable) {
            // already pruned
        }
    }
})->skip(fn () => ! temporalSchedulesSupported(), 'Temporal test server does not support the Schedules API.');
