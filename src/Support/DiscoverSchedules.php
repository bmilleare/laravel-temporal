<?php

namespace Keepsuit\LaravelTemporal\Support;

use Composer\ClassMapGenerator\ClassMapGenerator;
use Keepsuit\LaravelTemporal\Contracts\ScheduleDefinition;

class DiscoverSchedules
{
    /**
     * Get all the schedule definitions by searching the given directory.
     *
     * @return class-string[]
     */
    public static function within(string $schedulePath): array
    {
        if (! is_dir($schedulePath)) {
            return [];
        }

        $schedules = [];

        $generator = new ClassMapGenerator;
        $generator->scanPaths($schedulePath);

        foreach (array_keys($generator->getClassMap()->getMap()) as $class) {
            $reflection = new \ReflectionClass($class);

            if ($reflection->isInstantiable() && $reflection->implementsInterface(ScheduleDefinition::class)) {
                $schedules[] = $class;
            }
        }

        sort($schedules);

        return $schedules;
    }
}
