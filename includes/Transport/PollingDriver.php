<?php

namespace RTNotify\Transport;

use RTNotify\Abstracts\AbstractTransportDriver;

final class PollingDriver extends AbstractTransportDriver
{
    public function getName(): string
    {
        return 'polling';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function dispatch(array $event): array
    {
        unset($event);

        return $this->success();
    }
}
