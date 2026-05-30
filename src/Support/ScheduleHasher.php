<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Support;

use Temporal\Client\Schedule\Schedule;

/**
 * Computes a content hash of a desired Schedule, stored in the schedule memo
 * so `temporal:schedule:sync` can detect when a definition has changed and
 * needs updating. The hash covers the spec, action, policies and state — not
 * the schedule id (that is the reconciliation key).
 *
 * The hash is stable for a given application version. Across upgrades of this
 * package or the Temporal SDK the serialized form may shift, producing a new
 * hash and a single harmless `update()` on the next sync.
 */
final class ScheduleHasher
{
    public static function hash(Schedule $schedule): string
    {
        return sha1(serialize($schedule));
    }
}
