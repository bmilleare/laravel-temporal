<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Builder;

use DateInterval;
use DateTimeInterface;
use Spiral\Attributes\AttributeReader;
use Stringable;
use Temporal\Client\Schedule\Action\StartWorkflowAction;
use Temporal\Client\Schedule\Policy\ScheduleOverlapPolicy;
use Temporal\Client\Schedule\Policy\SchedulePolicies;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleOptions;
use Temporal\Client\Schedule\Spec\IntervalSpec;
use Temporal\Client\Schedule\Spec\ScheduleSpec;
use Temporal\Client\Schedule\Spec\ScheduleState;
use Temporal\Internal\Declaration\Reader\WorkflowReader;
use Temporal\Worker\WorkerFactoryInterface;

class ScheduleBuilder
{
    protected ?string $id = null;

    protected ScheduleSpec $spec;

    protected ?StartWorkflowAction $action = null;

    protected ?string $workflowId = null;

    protected SchedulePolicies $policies;

    protected ScheduleState $state;

    protected ScheduleOptions $options;

    public function __construct()
    {
        $this->spec = ScheduleSpec::new();
        $this->policies = SchedulePolicies::new();
        $this->state = ScheduleState::new();
        $this->options = ScheduleOptions::new();
    }

    public static function new(): ScheduleBuilder
    {
        return new ScheduleBuilder;
    }

    public function id(string $id): self
    {
        $self = clone $this;

        $self->id = $id;

        return $self;
    }

    /**
     * @param  non-empty-string|Stringable  $cron
     */
    public function cron(string|Stringable $cron): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withAddedCronString($cron);

        return $self;
    }

    public function interval(DateInterval|string $interval): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withAddedInterval(IntervalSpec::new($interval));

        return $self;
    }

    public function jitter(DateInterval|string $jitter): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withJitter($jitter);

        return $self;
    }

    public function startAt(DateTimeInterface|string $dateTime): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withStartTime($dateTime);

        return $self;
    }

    public function endAt(DateTimeInterface|string $dateTime): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withEndTime($dateTime);

        return $self;
    }

    public function timezone(string $timezone): self
    {
        $self = clone $this;

        $self->spec = $self->spec->withTimezoneName($timezone);

        return $self;
    }

    /**
     * @param  class-string|non-empty-string  $workflow  A workflow interface/class (its Temporal type is resolved) or a raw workflow type name.
     * @param  list<mixed>  $args  Positional arguments passed to the workflow.
     * @param  string|null  $taskQueue  Defaults to the configured `temporal.queue`.
     */
    public function startWorkflow(string $workflow, array $args = [], ?string $taskQueue = null): self
    {
        $self = clone $this;

        $self->action = StartWorkflowAction::new($this->resolveWorkflowType($workflow))
            ->withTaskQueue($taskQueue ?? config('temporal.queue') ?? WorkerFactoryInterface::DEFAULT_TASK_QUEUE);

        if ($args !== []) {
            $self->action = $self->action->withInput($args);
        }

        return $self;
    }

    /**
     * Set the base workflow id for started executions. Defaults to the schedule
     * id. Temporal appends the nominal scheduled time to each started run.
     */
    public function withWorkflowId(string $workflowId): self
    {
        $self = clone $this;

        $self->workflowId = $workflowId;

        return $self;
    }

    public function withOverlapPolicy(ScheduleOverlapPolicy $policy): self
    {
        $self = clone $this;

        $self->policies = $self->policies->withOverlapPolicy($policy);

        return $self;
    }

    public function withCatchupWindow(DateInterval|string $window): self
    {
        $self = clone $this;

        $self->policies = $self->policies->withCatchupWindow($window);

        return $self;
    }

    public function pauseOnFailure(bool $pauseOnFailure = true): self
    {
        $self = clone $this;

        $self->policies = $self->policies->withPauseOnFailure($pauseOnFailure);

        return $self;
    }

    public function paused(bool $paused = true): self
    {
        $self = clone $this;

        $self->state = $self->state->withPaused($paused);

        return $self;
    }

    public function note(string $note): self
    {
        $self = clone $this;

        $self->state = $self->state->withNotes($note);

        return $self;
    }

    public function limitedActions(bool $limitedActions = true): self
    {
        $self = clone $this;

        $self->state = $self->state->withLimitedActions($limitedActions);

        return $self;
    }

    public function remainingActions(int $remainingActions): self
    {
        $self = clone $this;

        $self->state = $self->state->withRemainingActions($remainingActions);

        return $self;
    }

    /**
     * @param  iterable<non-empty-string, mixed>  $memo
     */
    public function withMemo(iterable $memo): self
    {
        $self = clone $this;

        $self->options = $self->options->withMemo($memo);

        return $self;
    }

    /**
     * @param  iterable<non-empty-string, mixed>  $searchAttributes
     */
    public function withSearchAttributes(iterable $searchAttributes): self
    {
        $self = clone $this;

        $self->options = $self->options->withSearchAttributes($searchAttributes);

        return $self;
    }

    public function triggerImmediately(bool $triggerImmediately = true): self
    {
        $self = clone $this;

        $self->options = $self->options->withTriggerImmediately($triggerImmediately);

        return $self;
    }

    public function scheduleId(): ?string
    {
        return $this->id;
    }

    public function scheduleOptions(): ScheduleOptions
    {
        return $this->options;
    }

    public function build(): Schedule
    {
        $schedule = Schedule::new()
            ->withSpec($this->spec)
            ->withPolicies($this->policies)
            ->withState($this->state);

        if ($this->action !== null) {
            // Pin a deterministic base workflow id so the same definition hashes
            // consistently across sync runs (the SDK otherwise assigns a random
            // UUID per build). Precedence: explicit id, then schedule id, then
            // workflow type name.
            $workflowId = $this->workflowId ?? $this->id ?? $this->action->workflowType->name;

            $schedule = $schedule->withAction($this->action->withWorkflowId($workflowId));
        }

        return $schedule;
    }

    protected function resolveWorkflowType(string $workflow): string
    {
        if (class_exists($workflow) || interface_exists($workflow)) {
            return (new WorkflowReader(new AttributeReader))->fromClass($workflow)->getID();
        }

        return $workflow;
    }
}
