<?php

namespace RTNotify\Core;

final class NotificationPolicy
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function shouldProcess(array $event): bool
    {
        if (! $this->settings->isIntegrationEnabled((string) $event['source'])) {
            return false;
        }

        if (! $this->settings->isEventTypeEnabled((string) $event['type'])) {
            return false;
        }

        return ! $this->isDuplicate($event);
    }

    private function isDuplicate(array $event): bool
    {
        $ttl = 300;

        if ($ttl <= 0) {
            return false;
        }

        $key = 'rt_notify_dup_' . md5((string) ($event['dedupe_key'] ?? ''));

        if (get_transient($key)) {
            return true;
        }

        set_transient($key, 1, $ttl);

        return false;
    }
}
