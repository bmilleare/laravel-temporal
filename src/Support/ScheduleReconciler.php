<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Support;

/**
 * Pure reconciliation logic for `temporal:schedule:sync`: given the declared
 * schedules and what currently exists on the server, decide what to create,
 * update, leave untouched, or prune. No I/O — kept separate so it can be
 * exhaustively unit tested without a Temporal server.
 */
final class ScheduleReconciler
{
    /**
     * @param  array<non-empty-string, string>  $desired  Map of schedule id => content hash.
     * @param  array<non-empty-string, array{hash: ?string, managed: bool}>  $existing  Server schedules keyed by id.
     */
    public static function plan(array $desired, array $existing): ScheduleReconciliationPlan
    {
        $create = array_keys(array_diff_key($desired, $existing));

        $update = [];
        $unchanged = [];
        $conflicts = [];

        foreach (array_keys(array_intersect_key($desired, $existing)) as $id) {
            if (! $existing[$id]['managed']) {
                // A desired id that already exists but is NOT owned by this
                // package: never overwrite it. Surface it as a conflict so the
                // operator can rename their definition or adopt the schedule.
                $conflicts[] = $id;
            } elseif ($existing[$id]['hash'] === $desired[$id]) {
                $unchanged[] = $id;
            } else {
                $update[] = $id;
            }
        }

        $prunable = [];

        foreach (array_keys(array_diff_key($existing, $desired)) as $id) {
            // Only schedules we created (carrying the ownership marker) are ever
            // eligible for pruning — foreign schedules are left untouched.
            if ($existing[$id]['managed']) {
                $prunable[] = $id;
            }
        }

        return new ScheduleReconciliationPlan(
            create: $create,
            update: $update,
            unchanged: $unchanged,
            prunable: $prunable,
            conflicts: $conflicts,
        );
    }
}
