<?php

namespace RTNotify\Admin;

use RTNotify\Core\EventRepository;
use RTNotify\Core\Settings;

final class DebugPage
{
    private EventRepository $repository;

    private Settings $settings;

    public function __construct(EventRepository $repository, Settings $settings)
    {
        $this->repository = $repository;
        $this->settings = $settings;
    }

    public function register(): void
    {
        add_submenu_page(
            SettingsPage::PAGE_SLUG,
            __('Notifix Debug', 'notifix'),
            __('Debug', 'notifix'),
            'manage_options',
            'notifix-debug',
            [$this, 'render']
        );
    }

    public function render(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $events = $this->repository->recent(50);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Notifix Debug', 'notifix'); ?></h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Date', 'rt-notify'); ?></th>
                        <th><?php esc_html_e('Source', 'rt-notify'); ?></th>
                        <th><?php esc_html_e('Type', 'rt-notify'); ?></th>
                        <th><?php esc_html_e('Message', 'rt-notify'); ?></th>
                        <th><?php esc_html_e('Dispatch', 'rt-notify'); ?></th>
                        <th><?php esc_html_e('Error', 'rt-notify'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($events)) : ?>
                        <tr><td colspan="6"><?php esc_html_e('No events found.', 'rt-notify'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($events as $event) : ?>
                            <tr>
                                <td><?php echo esc_html($event['created_at']); ?></td>
                                <td><?php echo esc_html($event['source']); ?></td>
                                <td><?php echo esc_html($event['type']); ?></td>
                                <td><?php echo esc_html($event['message']); ?></td>
                                <td><?php echo esc_html($event['dispatch_status']); ?></td>
                                <td><?php echo esc_html($event['last_error']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
