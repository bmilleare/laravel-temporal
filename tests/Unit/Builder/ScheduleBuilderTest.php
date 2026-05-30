<?php

use Carbon\CarbonInterval;
use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;
use Temporal\Client\Schedule\Action\StartWorkflowAction;
use Temporal\Client\Schedule\Policy\ScheduleOverlapPolicy;
use Temporal\Client\Schedule\Schedule;

it('builds a schedule with a cron spec', function () {
    $schedule = ScheduleBuilder::new()
        ->id('daily-report')
        ->cron('0 9 * * *')
        ->build();

    expect($schedule)
        ->toBeInstanceOf(Schedule::class)
        ->and($schedule->spec->cronStringList)->toBe(['0 9 * * *']);
});

it('resolves the workflow type from a class for the start-workflow action', function () {
    $schedule = ScheduleBuilder::new()
        ->id('greet')
        ->cron('* * * * *')
        ->startWorkflow(DemoWorkflowInterface::class)
        ->build();

    expect($schedule->action)
        ->toBeInstanceOf(StartWorkflowAction::class)
        ->and($schedule->action->workflowType->name)->toBe('demo.greet');
});

it('passes workflow arguments as the action input', function () {
    $schedule = ScheduleBuilder::new()
        ->id('greet')
        ->startWorkflow(DemoWorkflowInterface::class, ['Alice', 42])
        ->build();

    expect($schedule->action->input->count())->toBe(2);
});

it('configures the full schedule spec', function () {
    $start = new DateTimeImmutable('2026-01-01 00:00:00');
    $end = new DateTimeImmutable('2026-12-31 00:00:00');

    $schedule = ScheduleBuilder::new()
        ->id('s')
        ->interval(CarbonInterval::hour())
        ->jitter(CarbonInterval::minutes(5))
        ->startAt($start)
        ->endAt($end)
        ->timezone('Europe/London')
        ->build();

    expect($schedule->spec)
        ->intervalList->toHaveCount(1)
        ->timezoneName->toBe('Europe/London')
        ->jitter->i->toBe(5);

    expect($schedule->spec->startTime?->format('Y-m-d'))->toBe('2026-01-01');
    expect($schedule->spec->endTime?->format('Y-m-d'))->toBe('2026-12-31');
});

it('configures policies and state', function () {
    $schedule = ScheduleBuilder::new()
        ->id('s')
        ->withOverlapPolicy(ScheduleOverlapPolicy::BufferOne)
        ->withCatchupWindow(CarbonInterval::minutes(10))
        ->pauseOnFailure()
        ->paused()
        ->note('maintenance')
        ->limitedActions()
        ->remainingActions(5)
        ->build();

    expect($schedule->policies)
        ->overlapPolicy->toBe(ScheduleOverlapPolicy::BufferOne)
        ->pauseOnFailure->toBeTrue()
        ->catchupWindow->i->toBe(10);

    expect($schedule->state)
        ->paused->toBeTrue()
        ->notes->toBe('maintenance')
        ->limitedActions->toBeTrue()
        ->remainingActions->toBe(5);
});

it('exposes the id and assembles schedule options', function () {
    $builder = ScheduleBuilder::new()
        ->id('reports')
        ->withMemo(['team' => 'data'])
        ->withSearchAttributes(['Env' => 'prod'])
        ->triggerImmediately();

    expect($builder->scheduleId())->toBe('reports');

    expect($builder->scheduleOptions())
        ->triggerImmediately->toBeTrue()
        ->memo->count()->toBe(1)
        ->searchAttributes->count()->toBe(1);
});

it('returns a null id when none is set', function () {
    expect(ScheduleBuilder::new()->scheduleId())->toBeNull();
});

it('targets the configured task queue for the workflow action and allows overriding it', function () {
    config()->set('temporal.queue', 'reports-queue');

    $default = ScheduleBuilder::new()
        ->id('s')
        ->startWorkflow(DemoWorkflowInterface::class)
        ->build();

    expect($default->action->taskQueue->name)->toBe('reports-queue');

    $override = ScheduleBuilder::new()
        ->id('s')
        ->startWorkflow(DemoWorkflowInterface::class, taskQueue: 'other-queue')
        ->build();

    expect($override->action->taskQueue->name)->toBe('other-queue');
});
