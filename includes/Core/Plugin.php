<?php

namespace RTNotify\Core;

use RTNotify\Admin\DebugPage;
use RTNotify\Admin\IntegrationCatalog;
use RTNotify\Transport\AblyDriver;
use RTNotify\Admin\SettingsPage;
use RTNotify\Transport\PollingDriver;
use RTNotify\Transport\PusherDriver;

final class Plugin
{
    private static ?self $instance = null;

    private Settings $settings;

    private EventRepository $repository;

    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function activate(): void
    {
        $repository = new EventRepository($GLOBALS['wpdb']);
        $repository->createTable();

        if (! wp_next_scheduled('rt_notify_daily_cleanup')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'rt_notify_daily_cleanup');
        }
    }

    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled('rt_notify_daily_cleanup');

        if ($timestamp) {
            wp_unschedule_event($timestamp, 'rt_notify_daily_cleanup');
        }
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->settings = new Settings();
        $this->repository = new EventRepository($GLOBALS['wpdb']);
        $normalizer = new EventNormalizer($this->settings);
        $policy = new NotificationPolicy($this->settings);
        $renderer = new TemplateRenderer($this->settings);
        $channelResolver = new ChannelResolver();
        $restController = new RestController($this->repository);
        $transportManager = new TransportManager($this->settings);
        $transportManager->register(new PollingDriver($this->settings));
        $transportManager->register(new PusherDriver($this->settings, $channelResolver));
        $transportManager->register(new AblyDriver($this->settings, $channelResolver));
        $eventManager = new EventManager($normalizer, $this->repository, $policy, $transportManager, $renderer);

        add_action('rt_notify_event', [$eventManager, 'emit'], 10, 1);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('admin_init', [$this, 'maybeRedirectLegacyAdminPage']);
        add_action('admin_menu', [new SettingsPage($this->settings, new IntegrationCatalog()), 'register']);
        add_action('admin_menu', [new DebugPage($this->repository, $this->settings), 'register']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
        add_action('rest_api_init', [$restController, 'registerRoutes']);
        add_action('wp_enqueue_scripts', [$this, 'enqueueFrontendAssets']);
        add_action('rt_notify_daily_cleanup', [$this->repository, 'pruneExpired']);

        (new AdapterRegistry($eventManager, $this->settings))->boot();

        $this->booted = true;
    }

    public function settings(): Settings
    {
        if (! isset($this->settings)) {
            $this->settings = new Settings();
        }

        return $this->settings;
    }

    public function registerSettings(): void
    {
        register_setting(
            'rt_notify_settings',
            Settings::OPTION_KEY,
            [
                'type'              => 'array',
                'sanitize_callback' => [$this->settings(), 'sanitize'],
                'default'           => $this->settings()->defaults(),
            ]
        );
    }

    public function maybeRedirectLegacyAdminPage(): void
    {
        if (! is_admin() || ! current_user_can('manage_options')) {
            return;
        }

        $page = isset($_GET['page']) ? sanitize_key((string) $_GET['page']) : '';

        if ($page === 'rt-notify') {
            wp_safe_redirect(admin_url('admin.php?page=' . SettingsPage::PAGE_SLUG));
            exit;
        }

        if ($page === 'rt-notify-debug') {
            wp_safe_redirect(admin_url('admin.php?page=notifix-debug'));
            exit;
        }
    }

    public function enqueueAdminAssets(string $hook): void
    {
        if (! in_array($hook, ['toplevel_page_' . SettingsPage::PAGE_SLUG, 'notifix_page_notifix-debug'], true)) {
            return;
        }

        wp_enqueue_style(
            'notifix-admin',
            RT_NOTIFY_URL . 'assets/css/admin.css',
            [],
            RT_NOTIFY_VERSION
        );

        wp_enqueue_script(
            'notifix-admin',
            RT_NOTIFY_URL . 'assets/js/admin.js',
            [],
            RT_NOTIFY_VERSION,
            true
        );
    }

    public function enqueueFrontendAssets(): void
    {
        if (is_admin()) {
            return;
        }

        $transport = (string) $this->settings()->get('transport_driver', 'polling');
        $channelResolver = new ChannelResolver();
        $transportConfig = $this->frontendTransportConfig($transport);

        if ($transportConfig === null) {
            return;
        }

        $this->enqueueProviderSdk($transport, $transportConfig);

        wp_enqueue_style(
            'rt-notify',
            RT_NOTIFY_URL . 'assets/css/style.css',
            [],
            RT_NOTIFY_VERSION
        );

        wp_enqueue_script(
            'rt-notify',
            RT_NOTIFY_URL . 'assets/js/app.js',
            [],
            RT_NOTIFY_VERSION,
            true
        );

        wp_localize_script(
            'rt-notify',
            'RTNotifyConfig',
            [
                'transportDriver' => $transport,
                'channelName'     => $channelResolver->channelName(),
                'eventName'       => $channelResolver->eventName(),
                'fallbackName'    => $this->settings()->get('identity.fallback_label', 'Someone'),
                'templates'       => $this->settings()->get('templates', []),
                'displayRules'    => $this->settings()->get('display_rules', []),
                'identity'        => $this->settings()->get('identity', []),
                'ui'              => $this->settings()->get('ui', []),
                'polling'         => [
                    'interval' => (int) $this->settings()->get('polling.interval', 12),
                    'limit'    => 5,
                    'restUrl'  => esc_url_raw(rest_url('notifix/v1/events')),
                ],
                'providers'       => [
                    'pusher'    => [
                        'key'     => $this->settings()->get('pusher.key', ''),
                        'cluster' => $this->settings()->get('pusher.cluster', 'mt1'),
                    ],
                    'ably'      => [
                        'key' => $this->settings()->get('ably.api_key', ''),
                    ],
                ],
            ]
        );
    }

    private function frontendTransportConfig(string $transport): ?array
    {
        if ($transport === 'pusher' && (string) $this->settings()->get('pusher.key', '') !== '') {
            return ['ready' => true];
        }

        if ($transport === 'ably' && (string) $this->settings()->get('ably.api_key', '') !== '') {
            return ['ready' => true];
        }

        if ($transport === 'polling') {
            return ['ready' => true];
        }

        return null;
    }

    private function enqueueProviderSdk(string $transport, array $transportConfig): void
    {
        unset($transportConfig);

        if ($transport === 'pusher') {
            $url = 'https://js.pusher.com/8.4.0/pusher.min.js';

            if ($url !== '') {
                wp_enqueue_script('rt-notify-provider-pusher', $url, [], null, true);
            }
        }

        if ($transport === 'ably') {
            $url = 'https://cdn.ably.com/lib/ably.min-2.js';

            if ($url !== '') {
                wp_enqueue_script('rt-notify-provider-ably', $url, [], null, true);
            }
        }
    }
}
