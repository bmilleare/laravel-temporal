<?php

namespace Keepsuit\LaravelTemporal\Tests\Fixtures\ScheduleDiscovery\Schedules;

/**
 * A plain class living alongside the definitions to prove discovery only
 * picks up classes implementing ScheduleDefinition.
 */
class NotASchedule
{
    public function handle(): void {}
}
