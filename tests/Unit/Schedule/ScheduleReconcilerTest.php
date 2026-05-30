<?php

use Keepsuit\LaravelTemporal\Support\ScheduleReconciler;

it('plans creation for desired schedules missing on the server', function () {
    $plan = ScheduleReconciler::plan(
        desired: ['daily-report' => 'hash-a', 'hourly-sync' => 'hash-b'],
        existing: [],
    );

    expect($plan)
        ->create->toBe(['daily-report', 'hourly-sync'])
        ->update->toBe([])
        ->unchanged->toBe([])
        ->prunable->toBe([]);
});

it('plans update for changed schedules and skips unchanged ones', function () {
    $plan = ScheduleReconciler::plan(
        desired: ['a' => 'new-hash', 'b' => 'same-hash'],
        existing: [
            'a' => ['hash' => 'old-hash', 'managed' => true],
            'b' => ['hash' => 'same-hash', 'managed' => true],
        ],
    );

    expect($plan)
        ->create->toBe([])
        ->update->toBe(['a'])
        ->unchanged->toBe(['b'])
        ->prunable->toBe([]);
});

it('marks managed orphans prunable but never foreign schedules', function () {
    $plan = ScheduleReconciler::plan(
        desired: ['keep' => 'h'],
        existing: [
            'keep' => ['hash' => 'h', 'managed' => true],
            'managed-orphan' => ['hash' => 'x', 'managed' => true],
            'foreign-orphan' => ['hash' => null, 'managed' => false],
        ],
    );

    expect($plan)
        ->create->toBe([])
        ->update->toBe([])
        ->unchanged->toBe(['keep'])
        ->prunable->toBe(['managed-orphan']);
});
