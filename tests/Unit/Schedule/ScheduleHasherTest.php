<?php

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;
use Keepsuit\LaravelTemporal\Support\ScheduleHasher;
use Keepsuit\LaravelTemporal\Tests\Fixtures\WorkflowDiscovery\Workflows\DemoWorkflowInterface;

it('produces a stable hash for identically configured schedules', function () {
    $build = fn () => ScheduleBuilder::new()
        ->id('x')
        ->cron('0 9 * * *')
        ->startWorkflow(DemoWorkflowInterface::class, ['Alice'])
        ->build();

    expect(ScheduleHasher::hash($build()))->toBe(ScheduleHasher::hash($build()));
});

it('produces a different hash when the schedule differs', function () {
    $a = ScheduleBuilder::new()->id('x')->cron('0 9 * * *')->startWorkflow(DemoWorkflowInterface::class)->build();
    $b = ScheduleBuilder::new()->id('x')->cron('0 10 * * *')->startWorkflow(DemoWorkflowInterface::class)->build();

    expect(ScheduleHasher::hash($a))->not->toBe(ScheduleHasher::hash($b));
});

it('detects drift in the declarative memo and search attributes', function () {
    $schedule = ScheduleBuilder::new()->id('x')->cron('0 9 * * *')->startWorkflow(DemoWorkflowInterface::class)->build();

    $base = ScheduleHasher::hash($schedule);

    expect(ScheduleHasher::hash($schedule, ['team' => 'data']))->not->toBe($base);
    expect(ScheduleHasher::hash($schedule, [], ['Env' => 'prod']))->not->toBe($base);
});

it('ignores memo and search-attribute declaration order', function () {
    $schedule = ScheduleBuilder::new()->id('x')->cron('0 9 * * *')->startWorkflow(DemoWorkflowInterface::class)->build();

    expect(ScheduleHasher::hash($schedule, ['a' => 1, 'b' => 2]))
        ->toBe(ScheduleHasher::hash($schedule, ['b' => 2, 'a' => 1]));
});
