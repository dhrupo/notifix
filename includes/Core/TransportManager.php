<?php

namespace RTNotify\Core;

use RTNotify\Contracts\TransportDriverInterface;

final class TransportManager
{
    /**
     * @var array<string, TransportDriverInterface>
     */
    private array $drivers = [];

    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function register(TransportDriverInterface $driver): void
    {
        $this->drivers[$driver->getName()] = $driver;
    }

    public function dispatch(array $event): array
    {
        $driver = $this->drivers[$this->settings->get('transport_driver', 'polling')] ?? null;

        if (! $driver instanceof TransportDriverInterface) {
            return [
                'success' => false,
                'status'  => 'missing_driver',
                'error'   => 'Configured transport driver was not found.',
            ];
        }

        if (! $driver->isConfigured()) {
            return [
                'success' => false,
                'status'  => 'not_configured',
                'error'   => 'Configured transport driver is not ready.',
            ];
        }

        return $driver->dispatch($event);
    }
}
