<?php

namespace RTNotify\Contracts;

interface AdapterInterface
{
    public function getSlug(): string;

    public function getLabel(): string;

    public function isPluginActive(): bool;

    public function shouldBoot(): bool;

    public function boot(): void;
}
