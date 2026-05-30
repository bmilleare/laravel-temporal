<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Contracts;

use Keepsuit\LaravelTemporal\Builder\ScheduleBuilder;

interface ScheduleDefinition
{
    /**
     * Configure the schedule: its id, spec, action, policies and state.
     */
    public function configure(ScheduleBuilder $schedule): ScheduleBuilder;
}
