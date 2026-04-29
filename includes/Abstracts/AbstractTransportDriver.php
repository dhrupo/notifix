<?php

namespace RTNotify\Abstracts;

use RTNotify\Contracts\TransportDriverInterface;
use RTNotify\Core\Settings;

abstract class AbstractTransportDriver implements TransportDriverInterface
{
    protected Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    protected function success(array $payload = []): array
    {
        return array_merge([
            'success' => true,
            'status'  => 'sent',
            'error'   => '',
        ], $payload);
    }

    protected function failure(string $status, string $error): array
    {
        return [
            'success' => false,
            'status'  => $status,
            'error'   => $error,
        ];
    }
}
