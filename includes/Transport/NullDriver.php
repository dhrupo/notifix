<?php

namespace RTNotify\Transport;

use RTNotify\Abstracts\AbstractTransportDriver;

final class NullDriver extends AbstractTransportDriver
{
    public function getName(): string
    {
        return 'null';
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function dispatch(array $event): array
    {
        return $this->success([
            'status' => 'skipped',
        ]);
    }
}
