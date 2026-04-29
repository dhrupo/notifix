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

    public function isEnabled(): bool
    {
        return $this->get('enabled', 'yes') === 'yes';
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
            'enabled'              => 'yes',
            'debug'                => 'no',
            'transport_driver'     => 'null',
            'channel_name'         => '',
            'transport_event_name' => 'notifix.event',
            'relay_endpoint'       => '',
            'socket_url'           => '',
            'auth_token'           => '',
            'connection_timeout'   => 5,
            'event_retention_days' => 30,
            'pusher'               => [
                'app_id'        => '',
                'key'           => '',
                'secret'        => '',
                'cluster'       => 'mt1',
                'client_js_url' => 'https://js.pusher.com/8.4.0/pusher.min.js',
            ],
            'ably'                 => [
                'api_key'       => '',
                'client_key'    => '',
                'client_id_prefix' => 'notifix',
                'client_js_url' => 'https://cdn.ably.com/lib/ably.min-2.js',
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
                'delay_before_first'         => 5,
                'cooldown_between'           => 8,
                'max_notifications_session'  => 5,
                'repeat_suppression_seconds' => 300,
                'device_targeting'           => 'all',
                'page_targeting'             => 'all',
            ],
            'identity'             => [
                'show_username'  => 'yes',
                'fallback_label' => 'Someone',
                'show_location'  => 'no',
                'mask_mode'      => 'safe',
            ],
            'ui'                   => [
                'position'        => 'bottom-left',
                'bg_color'        => '#111827',
                'text_color'      => '#ffffff',
                'accent_color'    => '#22c55e',
                'border_radius'   => 12,
                'shadow_intensity'=> 'medium',
                'max_width'       => 360,
                'spacing'         => 16,
                'duration'        => 6000,
                'animation_speed' => 280,
            ],
            'fake_events'          => [
                'enabled'         => 'no',
                'frequency'       => 0,
                'label'           => 'Demo',
            ],
        ];
    }

    public function sanitize(array $input): array
    {
        $defaults = $this->defaults();

        $sanitized = [
            'enabled'              => $this->sanitizeToggle($input['enabled'] ?? $defaults['enabled']),
            'debug'                => $this->sanitizeToggle($input['debug'] ?? $defaults['debug']),
            'transport_driver'     => sanitize_key($input['transport_driver'] ?? $defaults['transport_driver']),
            'channel_name'         => sanitize_text_field($input['channel_name'] ?? $defaults['channel_name']),
            'transport_event_name' => sanitize_text_field($input['transport_event_name'] ?? $defaults['transport_event_name']),
            'relay_endpoint'       => esc_url_raw($input['relay_endpoint'] ?? $defaults['relay_endpoint']),
            'socket_url'           => esc_url_raw($input['socket_url'] ?? $defaults['socket_url']),
            'auth_token'           => sanitize_text_field($input['auth_token'] ?? $defaults['auth_token']),
            'connection_timeout'   => max(1, absint($input['connection_timeout'] ?? $defaults['connection_timeout'])),
            'event_retention_days' => max(1, absint($input['event_retention_days'] ?? $defaults['event_retention_days'])),
            'pusher'               => [
                'app_id'        => sanitize_text_field($input['pusher']['app_id'] ?? $defaults['pusher']['app_id']),
                'key'           => sanitize_text_field($input['pusher']['key'] ?? $defaults['pusher']['key']),
                'secret'        => sanitize_text_field($input['pusher']['secret'] ?? $defaults['pusher']['secret']),
                'cluster'       => sanitize_key($input['pusher']['cluster'] ?? $defaults['pusher']['cluster']),
                'client_js_url' => esc_url_raw($input['pusher']['client_js_url'] ?? $defaults['pusher']['client_js_url']),
            ],
            'ably'                 => [
                'api_key'          => sanitize_text_field($input['ably']['api_key'] ?? $defaults['ably']['api_key']),
                'client_key'       => sanitize_text_field($input['ably']['client_key'] ?? $defaults['ably']['client_key']),
                'client_id_prefix' => sanitize_key($input['ably']['client_id_prefix'] ?? $defaults['ably']['client_id_prefix']),
                'client_js_url'    => esc_url_raw($input['ably']['client_js_url'] ?? $defaults['ably']['client_js_url']),
            ],
            'integrations'         => [],
            'event_types'          => [],
            'templates'            => [],
            'display_rules'        => [
                'delay_before_first'         => max(0, absint($input['display_rules']['delay_before_first'] ?? $defaults['display_rules']['delay_before_first'])),
                'cooldown_between'           => max(1, absint($input['display_rules']['cooldown_between'] ?? $defaults['display_rules']['cooldown_between'])),
                'max_notifications_session'  => max(1, absint($input['display_rules']['max_notifications_session'] ?? $defaults['display_rules']['max_notifications_session'])),
                'repeat_suppression_seconds' => max(0, absint($input['display_rules']['repeat_suppression_seconds'] ?? $defaults['display_rules']['repeat_suppression_seconds'])),
                'device_targeting'           => sanitize_key($input['display_rules']['device_targeting'] ?? $defaults['display_rules']['device_targeting']),
                'page_targeting'             => sanitize_key($input['display_rules']['page_targeting'] ?? $defaults['display_rules']['page_targeting']),
            ],
            'identity'             => [
                'show_username'  => $this->sanitizeToggle($input['identity']['show_username'] ?? $defaults['identity']['show_username']),
                'fallback_label' => sanitize_text_field($input['identity']['fallback_label'] ?? $defaults['identity']['fallback_label']),
                'show_location'  => $this->sanitizeToggle($input['identity']['show_location'] ?? $defaults['identity']['show_location']),
                'mask_mode'      => sanitize_key($input['identity']['mask_mode'] ?? $defaults['identity']['mask_mode']),
            ],
            'ui'                   => [
                'position'         => sanitize_key($input['ui']['position'] ?? $defaults['ui']['position']),
                'bg_color'         => sanitize_hex_color($input['ui']['bg_color'] ?? $defaults['ui']['bg_color']) ?: $defaults['ui']['bg_color'],
                'text_color'       => sanitize_hex_color($input['ui']['text_color'] ?? $defaults['ui']['text_color']) ?: $defaults['ui']['text_color'],
                'accent_color'     => sanitize_hex_color($input['ui']['accent_color'] ?? $defaults['ui']['accent_color']) ?: $defaults['ui']['accent_color'],
                'border_radius'    => max(0, absint($input['ui']['border_radius'] ?? $defaults['ui']['border_radius'])),
                'shadow_intensity' => sanitize_key($input['ui']['shadow_intensity'] ?? $defaults['ui']['shadow_intensity']),
                'max_width'        => max(220, absint($input['ui']['max_width'] ?? $defaults['ui']['max_width'])),
                'spacing'          => max(0, absint($input['ui']['spacing'] ?? $defaults['ui']['spacing'])),
                'duration'         => max(1000, absint($input['ui']['duration'] ?? $defaults['ui']['duration'])),
                'animation_speed'  => max(100, absint($input['ui']['animation_speed'] ?? $defaults['ui']['animation_speed'])),
            ],
            'fake_events'          => [
                'enabled'   => $this->sanitizeToggle($input['fake_events']['enabled'] ?? $defaults['fake_events']['enabled']),
                'frequency' => max(0, absint($input['fake_events']['frequency'] ?? $defaults['fake_events']['frequency'])),
                'label'     => sanitize_text_field($input['fake_events']['label'] ?? $defaults['fake_events']['label']),
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
