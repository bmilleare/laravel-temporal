<?php

use Keepsuit\LaravelTemporal\Facade\Temporal;
use Keepsuit\LaravelTemporal\TemporalRegistry;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Duplicates\DuplicateScheduleOne;
use Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Duplicates\DuplicateScheduleTwo;

beforeEach(fn () => Temporal::fake());

it('fails the trigger command when no schedule id is given', function () {
    $this->artisan('temporal:schedule:trigger', ['id' => '   '])
        ->expectsOutputToContain('A schedule id is required.')
        ->assertFailed();
});

it('fails the trigger command for an unknown overlap policy', function () {
    $this->artisan('temporal:schedule:trigger', ['id' => 'daily', '--overlap' => 'Skpi'])
        ->expectsOutputToContain('Unknown overlap policy')
        ->assertFailed();
});

it('fails the pause command when no schedule id is given', function () {
    $this->artisan('temporal:schedule:pause', ['id' => ''])
        ->expectsOutputToContain('A schedule id is required.')
        ->assertFailed();
});

it('fails sync when two definitions declare the same schedule id', function () {
    app()->bind(TemporalRegistry::class, fn () => (new TemporalRegistry)
        ->registerSchedules(DuplicateScheduleOne::class, DuplicateScheduleTwo::class));

    $this->artisan('temporal:schedule:sync')
        ->expectsOutputToContain('Duplicate schedule id [dupe]')
        ->assertFailed();
});
