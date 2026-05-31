<?php

namespace Keepsuit\LaravelTemporal\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputOption;

#[AsCommand('temporal:make:schedule')]
class ScheduleMakeCommand extends GeneratorCommand
{
    use Concerns\Stubs;

    protected $name = 'temporal:make:schedule';

    protected $description = 'Create a new temporal schedule definition class';

    protected $type = 'Schedule';

    protected function getStub(): string
    {
        return $this->resolveStubPath('schedule.stub');
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $rootNamespace = match (true) {
            is_dir($this->laravel->path('Temporal/Schedules')) => $rootNamespace.'\\Temporal',
            ! is_dir($this->laravel->path('Schedules')) => $rootNamespace.'\\Temporal',
            default => $rootNamespace,
        };

        return sprintf('%s\\Schedules', $rootNamespace);
    }

    /**
     * @return array<int, array{0: string, 1: string|null, 2: int, 3: string}>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the schedule class even if it already exists'],
        ];
    }
}
