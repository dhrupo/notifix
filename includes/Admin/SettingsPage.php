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
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('rt_notify_settings'); ?>

                <?php $this->renderSectionStart('General', __('Choose how Notifix delivers notifications on the frontend.', 'notifix'), true); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderSelectField(
                            __('Transport Driver', 'notifix'),
                            'transport_driver',
                            $settings['transport_driver'],
                            [
                                'polling' => __('Polling', 'notifix'),
                                'pusher'  => __('Pusher', 'notifix'),
                                'ably'    => __('Ably', 'notifix'),
                            ],
                            __('Choose polling for standard WordPress hosting, or a managed provider for push delivery.', 'notifix')
                        );
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Polling', __('Shown only when Polling is selected as the transport driver.', 'notifix'), false, 'polling'); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderNumberField(__('Polling Interval (seconds)', 'notifix'), 'polling][interval', $settings['polling']['interval'], 5, __('Keep this conservative for shared hosting. 10 to 15 seconds is usually enough.', 'notifix'));
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
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Ably', __('Shown only when Ably is selected as the realtime provider.', 'notifix'), false, 'ably'); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderTextField(__('API Key', 'notifix'), 'ably][api_key', $settings['ably']['api_key']);
                        ?>
                    </div>
                <?php $this->renderSectionEnd(); ?>

                <?php $this->renderSectionStart('Display Rules', __('Control when notifications begin and how often they appear.', 'notifix')); ?>
                    <div class="notifix-grid notifix-grid--2">
                        <?php
                        $this->renderNumberField(__('Delay Before First Notification', 'notifix'), 'display_rules][delay_before_first', $settings['display_rules']['delay_before_first'], 0);
                        $this->renderNumberField(__('Cooldown Between Notifications', 'notifix'), 'display_rules][cooldown_between', $settings['display_rules']['cooldown_between'], 1);
                        $this->renderNumberField(__('Max Notifications Per Session', 'notifix'), 'display_rules][max_notifications_session', $settings['display_rules']['max_notifications_session'], 1);
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

                <?php $this->renderSectionStart('Appearance', __('Customize the toast position, colors, and visibility timing.', 'notifix')); ?>
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
                        $this->renderNumberField(__('Duration (ms)', 'notifix'), 'ui][duration', $settings['ui']['duration'], 1000);
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
