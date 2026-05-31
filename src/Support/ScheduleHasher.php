<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Support;

use Temporal\Client\Schedule\Schedule;

/**
 * Computes a content hash of a desired Schedule, stored (as a plain string) in
 * the schedule memo so `temporal:schedule:sync` can detect when a definition
 * has changed and needs updating. The hash covers the spec, action, policies
 * and state — not the schedule id (that is the reconciliation key).
 *
 * `serialize()` is used purely locally to derive the hash; the serialized blob
 * is never sent to the server — only the resulting sha1 string lands in the
 * memo (the Schedule itself reaches Temporal via the SDK's protobuf mapper).
 *
 * Hashing the whole object graph is deliberate: it can never *under*-detect a
 * change (every field is covered automatically, including ones a future SDK
 * adds), which is the failure mode that matters — a missed change leaves a
 * stale schedule on the server with no signal. The trade-off is that across
 * upgrades of PHP, this package, or the Temporal SDK the serialized form may
 * shift, producing a new hash and a single harmless idempotent `update()` on
 * the next sync. Over-detection is cheap; under-detection is not.
 */
final class ScheduleHasher
{
    public static function hash(Schedule $schedule): string
    {
        return sha1(serialize($schedule));
    }
}
