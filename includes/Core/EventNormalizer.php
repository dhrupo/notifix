<?php

namespace RTNotify\Core;

final class EventNormalizer
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function normalize(array $event): array
    {
        $type = sanitize_key($event['type'] ?? '');
        $source = sanitize_key($event['source'] ?? 'custom');
        $actor = is_array($event['actor'] ?? null) ? $event['actor'] : [];
        $object = is_array($event['object'] ?? null) ? $event['object'] : [];
        $createdAt = ! empty($event['created_at']) ? sanitize_text_field((string) $event['created_at']) : current_time('mysql');

        $normalized = [
            'type'       => $type,
            'source'     => $source,
            'title'      => sanitize_text_field($event['title'] ?? ''),
            'message'    => sanitize_text_field($event['message'] ?? ''),
            'meta'       => is_array($event['meta'] ?? null) ? $event['meta'] : [],
            'actor'      => [
                'label'    => sanitize_text_field($actor['label'] ?? $this->settings->get('identity.fallback_label', 'Someone')),
                'username' => sanitize_text_field($actor['username'] ?? ''),
                'location' => sanitize_text_field($actor['location'] ?? ''),
            ],
            'object'     => [
                'id'    => absint($object['id'] ?? 0),
                'type'  => sanitize_key($object['type'] ?? 'item'),
                'label' => sanitize_text_field($object['label'] ?? ''),
            ],
            'visibility' => sanitize_key($event['visibility'] ?? 'public'),
            'dedupe_key' => sanitize_text_field($event['dedupe_key'] ?? ''),
            'created_at' => $createdAt,
            'timestamp'  => isset($event['timestamp']) ? absint($event['timestamp']) : current_time('timestamp'),
        ];

        if ($normalized['type'] === '') {
            throw new \InvalidArgumentException('Event type is required.');
        }

        if ($normalized['dedupe_key'] === '') {
            $normalized['dedupe_key'] = md5($this->buildDedupeKey($normalized));
        }

        if ($normalized['actor']['label'] === '') {
            $normalized['actor']['label'] = $this->settings->get('identity.fallback_label', 'Someone');
        }

        return $normalized;
    }

    private function buildDedupeKey(array $event): string
    {
        $meta = $event['meta'];
        $uniqueId = '';

        foreach (['order_id', 'payment_id', 'entry_id', 'submission_id', 'transaction_id', 'subscription_id'] as $key) {
            if (! empty($meta[$key])) {
                $uniqueId = $key . ':' . absint($meta[$key]);
                break;
            }
        }

        return implode('|', [
            $event['source'],
            $event['type'],
            $event['actor']['username'] ?: $event['actor']['label'],
            $event['object']['type'],
            $event['object']['id'],
            $event['object']['label'],
            $uniqueId,
        ]);
    }
}
