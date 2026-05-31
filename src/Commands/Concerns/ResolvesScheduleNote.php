<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands\Concerns;

/**
 * Resolves the shared `--note` option for the pause/unpause commands, falling
 * back to a default when it is absent or blank (the SDK rejects empty notes).
 */
trait ResolvesScheduleNote
{
    protected function noteOption(string $default): string
    {
        $note = $this->option('note');
        $note = is_string($note) ? trim($note) : '';

        return $note !== '' ? $note : $default;
    }
}
