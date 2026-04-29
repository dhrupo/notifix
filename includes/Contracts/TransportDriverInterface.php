<?php

namespace RTNotify\Contracts;

interface TransportDriverInterface
{
    public function getName(): string;

    public function isConfigured(): bool;

    public function dispatch(array $event): array;
}
