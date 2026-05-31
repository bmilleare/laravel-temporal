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

it('treats an unmanaged id collision as a conflict and never updates it', function () {
    $plan = ScheduleReconciler::plan(
        desired: ['shared' => 'desired-hash', 'mine' => 'h'],
        existing: [
            'shared' => ['hash' => null, 'managed' => false],
            'mine' => ['hash' => 'h', 'managed' => true],
        ],
    );

    expect($plan)
        ->create->toBe([])
        ->update->toBe([])
        ->unchanged->toBe(['mine'])
        ->prunable->toBe([])
        ->conflicts->toBe(['shared']);
});

it('updates a managed schedule whose stored hash is missing', function () {
    $plan = ScheduleReconciler::plan(
        desired: ['a' => 'hash'],
        existing: ['a' => ['hash' => null, 'managed' => true]],
    );

    expect($plan)
        ->update->toBe(['a'])
        ->conflicts->toBe([]);
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
