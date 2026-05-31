<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Support;

/**
 * The outcome of reconciling declared schedules against the schedules
 * currently present on the Temporal server. Each property is a list of
 * schedule ids.
 */
final readonly class ScheduleReconciliationPlan
{
    /**
     * @param  list<non-empty-string>  $create  Desired schedules missing on the server.
     * @param  list<non-empty-string>  $update  Desired schedules whose definition changed.
     * @param  list<non-empty-string>  $unchanged  Desired schedules already in sync.
     * @param  list<non-empty-string>  $prunable  Managed schedules on the server with no matching definition.
     */
    public function __construct(
        public array $create = [],
        public array $update = [],
        public array $unchanged = [],
        public array $prunable = [],
    ) {}
}
