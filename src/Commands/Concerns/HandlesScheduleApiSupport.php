<?php

declare(strict_types=1);

namespace Keepsuit\LaravelTemporal\Commands\Concerns;

use Temporal\Client\GRPC\StatusCode;
use Temporal\Exception\Client\ServiceClientException;

trait HandlesScheduleApiSupport
{
    /**
     * Translate the "Schedules API not implemented" case (older OSS servers and
     * some dev-server configurations) into a friendly operator-facing message.
     *
     * Returns true when the failure is an UNIMPLEMENTED status (caller should
     * stop with a non-zero exit code); returns false for any other gRPC status
     * so genuine failures (network, auth, namespace) are not masked and can be
     * rethrown by the caller.
     */
    protected function reportUnsupportedSchedulesApi(ServiceClientException $e): bool
    {
        if ($e->getCode() !== StatusCode::UNIMPLEMENTED) {
            return false;
        }

        $this->error('The Temporal Schedules API is not available on this server.');
        $this->line('Upgrade the Temporal server (or enable the Schedules API) to use the schedule commands.');

        return true;
    }
}
