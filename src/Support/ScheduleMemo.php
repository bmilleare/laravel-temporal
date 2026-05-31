<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Support;

use Temporal\DataConverter\EncodedCollection;

/**
 * Ownership and change-tracking markers stored in a schedule's memo so
 * `temporal:schedule:sync` can tell which schedules it manages and whether
 * a managed schedule has drifted from its definition. Memo is used (not a
 * custom search attribute) because it round-trips through listSchedules
 * without requiring namespace search-attribute registration.
 */
final class ScheduleMemo
{
    public const MANAGED_KEY = '_lt_managed';

    public const HASH_KEY = '_lt_hash';

    /**
     * @return array{_lt_managed: true, _lt_hash: string}
     */
    public static function markers(string $hash): array
    {
        return [
            self::MANAGED_KEY => true,
            self::HASH_KEY => $hash,
        ];
    }

    public static function isManaged(EncodedCollection $memo): bool
    {
        return self::value($memo, self::MANAGED_KEY) === true;
    }

    public static function hash(EncodedCollection $memo): ?string
    {
        $value = self::value($memo, self::HASH_KEY);

        return is_string($value) ? $value : null;
    }

    private static function value(EncodedCollection $memo, string $key): mixed
    {
        if (! array_key_exists($key, $memo->getValues())) {
            return null;
        }

        return $memo->getValue($key);
    }
}
