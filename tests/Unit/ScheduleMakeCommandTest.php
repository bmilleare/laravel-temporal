<?php

beforeEach(function () {
    $this->path = app_path('Temporal/Schedules/DailyReportSchedule.php');
    @unlink($this->path);
});

afterEach(function () {
    @unlink($this->path);
});

it('scaffolds a schedule definition class', function () {
    $this->artisan('temporal:make:schedule', ['name' => 'DailyReportSchedule'])
        ->assertSuccessful();

    expect(file_exists($this->path))->toBeTrue();

    $contents = (string) file_get_contents($this->path);

    expect($contents)
        ->toContain('namespace App\Temporal\Schedules;')
        ->toContain('class DailyReportSchedule implements ScheduleDefinition')
        ->toContain('public function configure(ScheduleBuilder $schedule): ScheduleBuilder');
});
