<?php

namespace RTNotify\Core;

final class Settings
{
    public const OPTION_KEY = 'rt_notify_settings';

    private ?array $cached = null;

    public function all(): array
    {
        if ($this->cached !== null) {
            return $this->cached;
        }

        $stored = get_option(self::OPTION_KEY, []);
        $stored = is_array($stored) ? $stored : [];

        $this->cached = $this->mergeRecursive($this->defaults(), $stored);
        $this->cached['transport_driver'] = $this->normalizeTransportDriver($this->cached['transport_driver'] ?? '');

        return $this->cached;
    }

    public function get(?string $path = null, $default = null)
    {
        $settings = $this->all();

        if ($path === null || $path === '') {
            return $settings;
        }

        $segments = explode('.', $path);
        $value = $settings;

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function isIntegrationEnabled(string $slug): bool
    {
        return $this->get('integrations.' . $slug, 'yes') === 'yes';
    }

    public function isEventTypeEnabled(string $type): bool
    {
        return $this->get('event_types.' . $type, 'yes') === 'yes';
    }

    public function defaults(): array
    {
        return [
            'transport_driver'     => 'polling',
            'polling'              => [
                'interval' => 12,
            ],
            'pusher'               => [
                'app_id'  => '',
                'key'     => '',
                'secret'  => '',
                'cluster' => 'mt1',
            ],
            'ably'                 => [
                'api_key' => '',
            ],
            'integrations'         => [
                'woocommerce'               => 'yes',
                'surecart'                  => 'yes',
                'fluentcart'                => 'yes',
                'edd'                       => 'yes',
                'fluentforms'               => 'yes',
                'wpforms'                   => 'yes',
                'gravityforms'              => 'yes',
                'ninjaforms'                => 'yes',
                'formidable'                => 'yes',
                'learndash'                 => 'yes',
                'tutorlms'                  => 'yes',
                'learnpress'                => 'yes',
                'lifterlms'                 => 'yes',
                'memberpress'               => 'yes',
                'pmpro'                     => 'yes',
                'restrict-content'          => 'yes',
                'paid-member-subscriptions' => 'yes',
            ],
            'event_types'          => [
                'purchase'             => 'yes',
                'order_paid'           => 'yes',
                'subscription_started' => 'yes',
                'subscription_renewed' => 'yes',
                'form_submit'          => 'yes',
                'lead_captured'        => 'yes',
                'registration_submitted' => 'yes',
                'course_enrolled'      => 'yes',
                'course_started'       => 'yes',
                'course_completed'     => 'yes',
                'membership_started'   => 'yes',
                'membership_renewed'   => 'yes',
                'membership_upgraded'  => 'yes',
            ],
            'templates'            => [
                'purchase'               => '{actor_name} purchased {object_name} {event_time_ago}',
                'order_paid'             => '{actor_name} completed payment for {object_name} {event_time_ago}',
                'subscription_started'   => '{actor_name} started a subscription for {object_name} {event_time_ago}',
                'subscription_renewed'   => '{actor_name} renewed {object_name} {event_time_ago}',
                'form_submit'            => '{actor_name} submitted {object_name} {event_time_ago}',
                'lead_captured'          => '{actor_name} signed up via {object_name} {event_time_ago}',
                'registration_submitted' => '{actor_name} registered through {object_name} {event_time_ago}',
                'course_enrolled'        => '{actor_name} enrolled in {object_name} {event_time_ago}',
                'course_started'         => '{actor_name} started {object_name} {event_time_ago}',
                'course_completed'       => '{actor_name} completed {object_name} {event_time_ago}',
                'membership_started'     => '{actor_name} joined {object_name} {event_time_ago}',
                'membership_renewed'     => '{actor_name} renewed {object_name} {event_time_ago}',
                'membership_upgraded'    => '{actor_name} upgraded to {object_name} {event_time_ago}',
            ],
            'display_rules'        => [
                'delay_before_first'        => 5,
                'cooldown_between'          => 8,
                'max_notifications_session' => 5,
            ],
            'identity'             => [
                'show_username'  => 'yes',
                'fallback_label' => 'Someone',
            ],
            'ui'                   => [
                'position'        => 'bottom-left',
                'bg_color'        => '#111827',
                'text_color'      => '#ffffff',
                'accent_color'    => '#22c55e',
                'border_radius'   => 12,
                'max_width'       => 360,
                'spacing'         => 16,
                'duration'        => 6000,
                'animation_speed' => 280,
            ],
        ];
    }

    public function sanitize(array $input): array
    {
        $defaults = $this->defaults();

        $sanitized = [
            'transport_driver'     => $this->normalizeTransportDriver($input['transport_driver'] ?? $defaults['transport_driver']),
            'polling'              => [
                'interval' => max(5, absint($input['polling']['interval'] ?? $defaults['polling']['interval'])),
            ],
            'pusher'               => [
                'app_id'  => sanitize_text_field($input['pusher']['app_id'] ?? $defaults['pusher']['app_id']),
                'key'     => sanitize_text_field($input['pusher']['key'] ?? $defaults['pusher']['key']),
                'secret'  => sanitize_text_field($input['pusher']['secret'] ?? $defaults['pusher']['secret']),
                'cluster' => sanitize_key($input['pusher']['cluster'] ?? $defaults['pusher']['cluster']),
            ],
            'ably'                 => [
                'api_key' => sanitize_text_field($input['ably']['api_key'] ?? $defaults['ably']['api_key']),
            ],
            'integrations'         => [],
            'event_types'          => [],
            'templates'            => [],
            'display_rules'        => [
                'delay_before_first'        => max(0, absint($input['display_rules']['delay_before_first'] ?? $defaults['display_rules']['delay_before_first'])),
                'cooldown_between'          => max(1, absint($input['display_rules']['cooldown_between'] ?? $defaults['display_rules']['cooldown_between'])),
                'max_notifications_session' => max(1, absint($input['display_rules']['max_notifications_session'] ?? $defaults['display_rules']['max_notifications_session'])),
            ],
            'identity'             => [
                'show_username'  => $this->sanitizeToggle($input['identity']['show_username'] ?? $defaults['identity']['show_username']),
                'fallback_label' => sanitize_text_field($input['identity']['fallback_label'] ?? $defaults['identity']['fallback_label']),
            ],
            'ui'                   => [
                'position'         => sanitize_key($input['ui']['position'] ?? $defaults['ui']['position']),
                'bg_color'         => sanitize_hex_color($input['ui']['bg_color'] ?? $defaults['ui']['bg_color']) ?: $defaults['ui']['bg_color'],
                'text_color'       => sanitize_hex_color($input['ui']['text_color'] ?? $defaults['ui']['text_color']) ?: $defaults['ui']['text_color'],
                'accent_color'     => sanitize_hex_color($input['ui']['accent_color'] ?? $defaults['ui']['accent_color']) ?: $defaults['ui']['accent_color'],
                'border_radius'    => max(0, absint($input['ui']['border_radius'] ?? $defaults['ui']['border_radius'])),
                'max_width'        => max(220, absint($input['ui']['max_width'] ?? $defaults['ui']['max_width'])),
                'spacing'          => max(0, absint($input['ui']['spacing'] ?? $defaults['ui']['spacing'])),
                'duration'         => max(1000, absint($input['ui']['duration'] ?? $defaults['ui']['duration'])),
                'animation_speed'  => max(100, absint($input['ui']['animation_speed'] ?? $defaults['ui']['animation_speed'])),
            ],
        ];

        foreach (array_keys($defaults['integrations']) as $key) {
            $sanitized['integrations'][$key] = $this->sanitizeToggle($input['integrations'][$key] ?? $defaults['integrations'][$key]);
        }

        foreach (array_keys($defaults['event_types']) as $key) {
            $sanitized['event_types'][$key] = $this->sanitizeToggle($input['event_types'][$key] ?? $defaults['event_types'][$key]);
        }

        foreach (array_keys($defaults['templates']) as $key) {
            $sanitized['templates'][$key] = sanitize_text_field($input['templates'][$key] ?? $defaults['templates'][$key]);
        }

        $this->cached = null;

        return $sanitized;
    }

    private function sanitizeToggle($value): string
    {
        return $value === 'no' ? 'no' : 'yes';
    }

    private function normalizeTransportDriver($value): string
    {
        $transport = sanitize_key((string) $value);

        if ($transport === 'null' || $transport === '') {
            return 'polling';
        }

        if (in_array($transport, ['polling', 'pusher', 'ably'], true)) {
            return $transport;
        }

        return 'polling';
    }

    private function mergeRecursive(array $defaults, array $settings): array
    {
        foreach ($defaults as $key => $value) {
            if (! array_key_exists($key, $settings)) {
                $settings[$key] = $value;
                continue;
            }

            if (is_array($value) && is_array($settings[$key])) {
                $settings[$key] = $this->mergeRecursive($value, $settings[$key]);
            }
        }

        return $settings;
    }
}
