<?php

namespace RTNotify\Admin;

use RTNotify\Core\Settings;

final class SettingsPage
{
    public const PAGE_SLUG = 'notifix';

    private Settings $settings;

    private IntegrationCatalog $integrationCatalog;

    public function __construct(Settings $settings, IntegrationCatalog $integrationCatalog)
    {
        $this->settings = $settings;
        $this->integrationCatalog = $integrationCatalog;
    }

    public function register(): void
    {
        add_menu_page(
            __('Notifix', 'notifix'),
            __('Notifix', 'notifix'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
            'dashicons-megaphone'
        );

        add_submenu_page(
            self::PAGE_SLUG,
            __('Notifix Legacy Redirect', 'notifix'),
            __('Notifix Legacy Redirect', 'notifix'),
            'manage_options',
            'rt-notify',
            [$this, 'redirectLegacy']
        );

        remove_submenu_page(self::PAGE_SLUG, 'rt-notify');
    }

    public function redirectLegacy(): void
    {
        wp_safe_redirect(admin_url('admin.php?page=' . self::PAGE_SLUG));
        exit;
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $settings = $this->settings->all();
        $integrations = $this->integrationCatalog->grouped();
        ?>
        <div class="wrap notifix-admin">
            <div class="notifix-admin__hero">
                <div>
                    <h1><?php esc_html_e('Notifix Settings', 'notifix'); ?></h1>
                    <p><?php esc_html_e('Configure realtime delivery, appearance, identity, and plugin integrations from one place.', 'notifix'); ?></p>
                </div>
                <div class="notifix-admin__hero-meta">
                    <span class="notifix-badge notifix-badge--muted"><?php echo esc_html__('Plugin', 'notifix') . ': ' . esc_html(RT_NOTIFY_VERSION); ?></span>
                    <span class="notifix-badge"><?php echo esc_html__('Page', 'notifix') . ': page=' . esc_html(self::PAGE_SLUG); ?></span>
                </div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('rt_notify_settings'); ?>

                <?php $this->renderSectionStart('General', __('Engine status, persistence, and the main realtime provider.', 'notifix'), true); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderToggleField(__('Enable Engine', 'notifix'), 'enabled', $settings['enabled']);
                        $this->renderToggleField(__('Debug Mode', 'notifix'), 'debug', $settings['debug']);
                        $this->renderSelectField(
                            __('Transport Driver', 'notifix'),
                            'transport_driver',
                            $settings['transport_driver'],
                            [
                                'null'   => __('Disabled', 'notifix'),
                                'pusher' => __('Pusher', 'notifix'),
                                'ably'   => __('Ably', 'notifix'),
                            ],
                            __('Use a managed provider. The old custom websocket path is intentionally hidden from the admin UI.', 'notifix')
                        );
                        $this->renderTextField(__('Channel Name', 'notifix'), 'channel_name', $settings['channel_name'], __('Leave empty to use the generated site-specific channel.', 'notifix'));
                        $this->renderTextField(__('Event Name', 'notifix'), 'transport_event_name', $settings['transport_event_name']);
                        $this->renderNumberField(__('Retention Days', 'notifix'), 'event_retention_days', $settings['event_retention_days'], 1);
                        $this->renderNumberField(__('Connection Timeout', 'notifix'), 'connection_timeout', $settings['connection_timeout'], 1);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Pusher', __('Shown only when Pusher is selected as the realtime provider.', 'notifix'), false, 'pusher'); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderTextField(__('App ID', 'notifix'), 'pusher][app_id', $settings['pusher']['app_id']);
                        $this->renderTextField(__('Key', 'notifix'), 'pusher][key', $settings['pusher']['key']);
                        $this->renderTextField(__('Secret', 'notifix'), 'pusher][secret', $settings['pusher']['secret']);
                        $this->renderTextField(__('Cluster', 'notifix'), 'pusher][cluster', $settings['pusher']['cluster']);
                        $this->renderUrlField(__('Client SDK URL', 'notifix'), 'pusher][client_js_url', $settings['pusher']['client_js_url']);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Ably', __('Shown only when Ably is selected as the realtime provider.', 'notifix'), false, 'ably'); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderTextField(__('Publish API Key', 'notifix'), 'ably][api_key', $settings['ably']['api_key']);
                        $this->renderTextField(__('Frontend Client Key', 'notifix'), 'ably][client_key', $settings['ably']['client_key'], __('Prefer a restricted frontend key or token-auth flow.', 'notifix'));
                        $this->renderTextField(__('Client ID Prefix', 'notifix'), 'ably][client_id_prefix', $settings['ably']['client_id_prefix']);
                        $this->renderUrlField(__('Client SDK URL', 'notifix'), 'ably][client_js_url', $settings['ably']['client_js_url']);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Display Rules', __('Control timing and frequency for when notifications appear.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderNumberField(__('Delay Before First Notification', 'notifix'), 'display_rules][delay_before_first', $settings['display_rules']['delay_before_first'], 0);
                        $this->renderNumberField(__('Cooldown Between Notifications', 'notifix'), 'display_rules][cooldown_between', $settings['display_rules']['cooldown_between'], 1);
                        $this->renderNumberField(__('Max Notifications Per Session', 'notifix'), 'display_rules][max_notifications_session', $settings['display_rules']['max_notifications_session'], 1);
                        $this->renderNumberField(__('Repeat Suppression Window', 'notifix'), 'display_rules][repeat_suppression_seconds', $settings['display_rules']['repeat_suppression_seconds'], 0);
                        $this->renderSelectField(
                            __('Device Targeting', 'notifix'),
                            'display_rules][device_targeting',
                            $settings['display_rules']['device_targeting'],
                            [
                                'all'     => __('All Devices', 'notifix'),
                                'desktop' => __('Desktop Only', 'notifix'),
                                'mobile'  => __('Mobile Only', 'notifix'),
                            ]
                        );
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Identity', __('Decide how user identity is exposed in the public notification text.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderToggleField(__('Show Username', 'notifix'), 'identity][show_username', $settings['identity']['show_username']);
                        $this->renderTextField(__('Fallback Label', 'notifix'), 'identity][fallback_label', $settings['identity']['fallback_label']);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Appearance', __('Customize the toast layout, colors, spacing, and position.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderSelectField(
                            __('Position', 'notifix'),
                            'ui][position',
                            $settings['ui']['position'],
                            [
                                'top-left'      => 'top-left',
                                'top-right'     => 'top-right',
                                'bottom-left'   => 'bottom-left',
                                'bottom-right'  => 'bottom-right',
                                'top-center'    => 'top-center',
                                'bottom-center' => 'bottom-center',
                            ]
                        );
                        $this->renderColorField(__('Background Color', 'notifix'), 'ui][bg_color', $settings['ui']['bg_color']);
                        $this->renderColorField(__('Text Color', 'notifix'), 'ui][text_color', $settings['ui']['text_color']);
                        $this->renderColorField(__('Accent Color', 'notifix'), 'ui][accent_color', $settings['ui']['accent_color']);
                        $this->renderNumberField(__('Border Radius', 'notifix'), 'ui][border_radius', $settings['ui']['border_radius'], 0);
                        $this->renderNumberField(__('Max Width', 'notifix'), 'ui][max_width', $settings['ui']['max_width'], 220);
                        $this->renderNumberField(__('Spacing', 'notifix'), 'ui][spacing', $settings['ui']['spacing'], 0);
                        $this->renderNumberField(__('Duration (ms)', 'notifix'), 'ui][duration', $settings['ui']['duration'], 1000);
                        $this->renderNumberField(__('Animation Speed (ms)', 'notifix'), 'ui][animation_speed', $settings['ui']['animation_speed'], 100);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Fake Events', __('Optional synthetic activity when no real events are available.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderToggleField(__('Enable Fake Events', 'notifix'), 'fake_events][enabled', $settings['fake_events']['enabled']);
                        $this->renderNumberField(__('Fake Event Frequency', 'notifix'), 'fake_events][frequency', $settings['fake_events']['frequency'], 0);
                        $this->renderTextField(__('Fake Event Label', 'notifix'), 'fake_events][label', $settings['fake_events']['label']);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Integrations', __('Only active plugins should be configurable. Inactive or missing integrations are visible but locked.', 'notifix')); ?>
                    <?php foreach ($integrations as $family => $items) : ?>
                        <div class="notifix-family">
                            <h3><?php echo esc_html($family); ?></h3>
                            <div class="notifix-integration-list">
                                <?php foreach ($items as $item) : ?>
                                    <div class="notifix-integration-card <?php echo $item['available'] ? 'is-available' : 'is-unavailable'; ?>">
                                        <div class="notifix-integration-card__header">
                                            <strong><?php echo esc_html($item['label']); ?></strong>
                                            <span class="notifix-badge <?php echo $item['available'] ? 'notifix-badge--success' : 'notifix-badge--muted'; ?>">
                                                <?php echo esc_html($item['available'] ? __('Active', 'notifix') : __('Unavailable', 'notifix')); ?>
                                            </span>
                                        </div>
                                        <div class="notifix-integration-card__body">
                                            <?php
                                            $this->renderSelectField(
                                                __('Integration State', 'notifix'),
                                                'integrations][' . $item['slug'],
                                                $settings['integrations'][$item['slug']] ?? 'no',
                                                [
                                                    'yes' => __('Enabled', 'notifix'),
                                                    'no'  => __('Disabled', 'notifix'),
                                                ],
                                                $item['available'] ? __('Plugin detected and ready for events.', 'notifix') : __('Plugin is not installed or not active on this WordPress setup.', 'notifix'),
                                                ! $item['available']
                                            );
                                            ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Event Types', __('Enable or disable each normalized event family.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--3">
                        <?php foreach ($settings['event_types'] as $key => $value) : ?>
                            <?php $this->renderToggleField(ucwords(str_replace('_', ' ', $key)), 'event_types][' . $key, $value); ?>
                        <?php endforeach; ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Templates', __('Control the final notification message per event type.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--1">
                        <?php foreach ($settings['templates'] as $key => $value) : ?>
                            <?php $this->renderTextField(ucwords(str_replace('_', ' ', $key)), 'templates][' . $key, $value, __('Available tokens: {actor_name}, {object_name}, {event_time_ago}, {actor_location}, {source}, {event_type}', 'notifix'), 'large-text'); ?>
                        <?php endforeach; ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <div class="notifix-admin__footer">
                    <?php submit_button(__('Save Settings', 'notifix'), 'primary large', 'submit', false); ?>
                </div>
            </form>
        </div>
        <?php
    }

    private function renderSectionStart(string $title, string $description, bool $open = false, string $providerSection = ''): void
    {
        ?>
        <details class="notifix-section" <?php echo $open ? 'open' : ''; ?> <?php echo $providerSection !== '' ? 'data-provider-section="' . esc_attr($providerSection) . '"' : ''; ?>>
            <summary class="notifix-section__summary">
                <span>
                    <strong><?php echo esc_html($title); ?></strong>
                    <small><?php echo esc_html($description); ?></small>
                </span>
                <span class="notifix-section__icon" aria-hidden="true">+</span>
            </summary>
            <div class="notifix-section__body">
        <?php
    }

    private function renderSectionEnd(): void
    {
        ?>
            </div>
        </details>
        <?php
    }

    private function renderToggleField(string $label, string $path, string $value, string $help = '', bool $disabled = false): void
    {
        $this->renderSelectField($label, $path, $value, ['yes' => __('Enabled', 'notifix'), 'no' => __('Disabled', 'notifix')], $help, $disabled);
    }

    private function renderSelectField(string $label, string $path, string $value, array $options, string $help = '', bool $disabled = false): void
    {
        ?>
        <div class="notifix-field">
            <label class="notifix-field__label"><?php echo esc_html($label); ?></label>
            <select class="notifix-field__control" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[<?php echo esc_attr($path); ?>]" <?php disabled($disabled); ?>>
                <?php foreach ($options as $optionValue => $optionLabel) : ?>
                    <option value="<?php echo esc_attr($optionValue); ?>" <?php selected($value, $optionValue); ?>><?php echo esc_html($optionLabel); ?></option>
                <?php endforeach; ?>
            </select>
            <?php if ($help !== '') : ?>
                <p class="notifix-field__help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderTextField(string $label, string $path, string $value, string $help = '', string $class = 'regular-text', bool $disabled = false): void
    {
        ?>
        <div class="notifix-field">
            <label class="notifix-field__label"><?php echo esc_html($label); ?></label>
            <input class="notifix-field__control <?php echo esc_attr($class); ?>" type="text" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[<?php echo esc_attr($path); ?>]" value="<?php echo esc_attr($value); ?>" <?php disabled($disabled); ?>>
            <?php if ($help !== '') : ?>
                <p class="notifix-field__help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderUrlField(string $label, string $path, string $value, string $help = '', bool $disabled = false): void
    {
        ?>
        <div class="notifix-field">
            <label class="notifix-field__label"><?php echo esc_html($label); ?></label>
            <input class="notifix-field__control regular-text" type="url" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[<?php echo esc_attr($path); ?>]" value="<?php echo esc_attr($value); ?>" <?php disabled($disabled); ?>>
            <?php if ($help !== '') : ?>
                <p class="notifix-field__help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderNumberField(string $label, string $path, $value, int $min = 0, string $help = '', bool $disabled = false): void
    {
        ?>
        <div class="notifix-field">
            <label class="notifix-field__label"><?php echo esc_html($label); ?></label>
            <input class="notifix-field__control small-text" type="number" min="<?php echo esc_attr((string) $min); ?>" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[<?php echo esc_attr($path); ?>]" value="<?php echo esc_attr((string) $value); ?>" <?php disabled($disabled); ?>>
            <?php if ($help !== '') : ?>
                <p class="notifix-field__help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }

    private function renderColorField(string $label, string $path, string $value, string $help = '', bool $disabled = false): void
    {
        ?>
        <div class="notifix-field">
            <label class="notifix-field__label"><?php echo esc_html($label); ?></label>
            <input class="notifix-field__control notifix-field__control--color" type="color" name="<?php echo esc_attr(Settings::OPTION_KEY); ?>[<?php echo esc_attr($path); ?>]" value="<?php echo esc_attr($value); ?>" <?php disabled($disabled); ?>>
            <?php if ($help !== '') : ?>
                <p class="notifix-field__help"><?php echo esc_html($help); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
