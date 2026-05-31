<?php

namespace Keepsuit\LaravelTemporal\Testing\Fakes;

use Spiral\Attributes\AttributeReader;
use Temporal\Client\ClientOptions;
use Temporal\Client\Common\ClientContextTrait;
use Temporal\Client\Common\Paginator;
use Temporal\Client\GRPC\ServiceClientInterface;
use Temporal\Client\Schedule\Info\ScheduleListEntry;
use Temporal\Client\Schedule\Schedule;
use Temporal\Client\Schedule\ScheduleHandle;
use Temporal\Client\Schedule\ScheduleOptions;
use Temporal\Client\ScheduleClientInterface;
use Temporal\DataConverter\DataConverter;
use Temporal\DataConverter\DataConverterInterface;
use Temporal\Internal\Marshaller\Mapper\AttributeMapperFactory;
use Temporal\Internal\Marshaller\Marshaller;
use Temporal\Internal\Marshaller\MarshallerInterface;
use Temporal\Internal\Marshaller\ProtoToArrayConverter;

/**
 * In-memory schedule client used by Temporal::fake(). ScheduleClient is final
 * so this implements the interface directly, recording createSchedule() calls
 * instead of hitting the server. Handle operations are not exercised under
 * fake() — they are covered by integration tests.
 */
class FakeScheduleClient implements ScheduleClientInterface
{
    use ClientContextTrait;

    protected ClientOptions $clientOptions;

    protected DataConverterInterface $converter;

    protected MarshallerInterface $marshaller;

    protected ProtoToArrayConverter $protoConverter;

    /**
     * @var list<array{id: ?string, schedule: Schedule, options: ?ScheduleOptions}>
     */
    protected array $created = [];

    public function __construct(
        ServiceClientInterface $serviceClient,
        ?ClientOptions $options = null,
        ?DataConverterInterface $converter = null,
    ) {
        $this->clientOptions = $options ?? new ClientOptions;
        $this->converter = $converter ?? DataConverter::createDefault();
        $this->marshaller = new Marshaller(new AttributeMapperFactory(new AttributeReader));
        $this->protoConverter = new ProtoToArrayConverter($this->converter);
        $this->client = $serviceClient;
    }

    public function createSchedule(
        Schedule $schedule,
        ?ScheduleOptions $options = null,
        ?string $scheduleId = null,
    ): ScheduleHandle {
        $this->created[] = [
            'id' => $scheduleId,
            'schedule' => $schedule,
            'options' => $options,
        ];

        $id = $scheduleId !== null && $scheduleId !== ''
            ? $scheduleId
            : 'fake-schedule-'.count($this->created);

        return $this->getHandle($id);
    }

    public function getHandle(string $scheduleID, ?string $namespace = null): ScheduleHandle
    {
        return new ScheduleHandle(
            $this->client,
            $this->clientOptions,
            $this->converter,
            $this->marshaller,
            $this->protoConverter,
            $namespace ?? $this->clientOptions->namespace,
            $scheduleID,
        );
    }

    public function listSchedules(?string $namespace = null, int $pageSize = 0, string $query = ''): Paginator
    {
        return Paginator::createFromGenerator($this->emptyPages(), static fn (): int => 0);
    }

    /**
     * @return \Generator<int, list<ScheduleListEntry>>
     */
    protected function emptyPages(): \Generator
    {
        yield from [];
    }

    /**
     * @return list<array{id: ?string, schedule: Schedule, options: ?ScheduleOptions}>
     */
    public function createdSchedules(): array
    {
        return $this->created;
    }
}
