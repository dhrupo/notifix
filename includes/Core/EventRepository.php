<?php

namespace RTNotify\Core;

use wpdb;

final class EventRepository
{
    private wpdb $wpdb;

    private Settings $settings;

    public function __construct(wpdb $wpdb, Settings $settings)
    {
        $this->wpdb = $wpdb;
        $this->settings = $settings;
    }

    public function table(): string
    {
        return $this->wpdb->prefix . 'rt_events';
    }

    public function createTable(): void
    {
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $this->wpdb->get_charset_collate();
        $table = $this->table();

        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            type VARCHAR(50) NOT NULL,
            source VARCHAR(50) NOT NULL,
            title TEXT NOT NULL,
            message TEXT NOT NULL,
            meta LONGTEXT NULL,
            actor LONGTEXT NULL,
            object LONGTEXT NULL,
            visibility VARCHAR(20) NOT NULL DEFAULT 'public',
            dedupe_key VARCHAR(191) NULL,
            dispatch_status VARCHAR(20) NOT NULL DEFAULT 'pending',
            last_error TEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY type_created_at (type, created_at),
            KEY source_created_at (source, created_at),
            KEY dispatch_status_created_at (dispatch_status, created_at),
            KEY dedupe_key (dedupe_key)
        ) {$charset};";

        dbDelta($sql);
    }

    public function insert(array $event): int
    {
        $this->wpdb->insert(
            $this->table(),
            [
                'type'            => $event['type'],
                'source'          => $event['source'],
                'title'           => $event['title'],
                'message'         => $event['message'],
                'meta'            => wp_json_encode($event['meta']),
                'actor'           => wp_json_encode($event['actor']),
                'object'          => wp_json_encode($event['object']),
                'visibility'      => $event['visibility'],
                'dedupe_key'      => $event['dedupe_key'],
                'dispatch_status' => 'pending',
                'last_error'      => '',
                'created_at'      => $event['created_at'],
            ],
            ['%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return (int) $this->wpdb->insert_id;
    }

    public function updateDispatchStatus(int $eventId, string $status, string $error = ''): void
    {
        $this->wpdb->update(
            $this->table(),
            [
                'dispatch_status' => sanitize_key($status),
                'last_error'      => sanitize_text_field($error),
            ],
            ['id' => $eventId],
            ['%s', '%s'],
            ['%d']
        );
    }

    public function recent(int $limit = 50): array
    {
        $limit = max(1, absint($limit));
        $table = $this->table();

        $rows = $this->wpdb->get_results(
            $this->wpdb->prepare("SELECT * FROM {$table} ORDER BY created_at DESC LIMIT %d", $limit),
            ARRAY_A
        );

        return array_map([$this, 'decodeRow'], $rows ?: []);
    }

    public function pruneExpired(): void
    {
        $days = max(1, (int) $this->settings->get('event_retention_days', 30));
        $table = $this->table();
        $cutoff = gmdate('Y-m-d H:i:s', current_time('timestamp') - ($days * DAY_IN_SECONDS));

        $this->wpdb->query(
            $this->wpdb->prepare(
                "DELETE FROM {$table} WHERE created_at < %s",
                $cutoff
            )
        );
    }

    private function decodeRow(array $row): array
    {
        $row['meta'] = json_decode((string) $row['meta'], true) ?: [];
        $row['actor'] = json_decode((string) $row['actor'], true) ?: [];
        $row['object'] = json_decode((string) $row['object'], true) ?: [];

        return $row;
    }
}
