<?php

namespace RTNotify\Core;

use wpdb;

final class EventRepository
{
    private wpdb $wpdb;

    public function __construct(wpdb $wpdb)
    {
        $this->wpdb = $wpdb;
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

    public function publicFeed(int $sinceId = 0, int $limit = 5): array
    {
        $sinceId = max(0, $sinceId);
        $limit = max(1, min(20, absint($limit)));
        $table = $this->table();

        if ($sinceId > 0) {
            $rows = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} WHERE visibility = %s AND dispatch_status IN ('sent', 'skipped') AND id > %d ORDER BY id ASC LIMIT %d",
                    'public',
                    $sinceId,
                    $limit
                ),
                ARRAY_A
            );
        } else {
            $rows = $this->wpdb->get_results(
                $this->wpdb->prepare(
                    "SELECT * FROM {$table} WHERE visibility = %s AND dispatch_status IN ('sent', 'skipped') ORDER BY id DESC LIMIT %d",
                    'public',
                    $limit
                ),
                ARRAY_A
            );
            $rows = array_reverse($rows ?: []);
        }

        $items = array_map([$this, 'decodeRow'], $rows ?: []);

        return array_map([$this, 'toPublicEvent'], $items);
    }

    public function pruneExpired(): void
    {
        $days = 30;
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
        $row['id'] = (int) $row['id'];
        $row['meta'] = json_decode((string) $row['meta'], true) ?: [];
        $row['actor'] = json_decode((string) $row['actor'], true) ?: [];
        $row['object'] = json_decode((string) $row['object'], true) ?: [];

        return $row;
    }

    private function toPublicEvent(array $row): array
    {
        return [
            'id'         => (int) $row['id'],
            'type'       => (string) $row['type'],
            'source'     => (string) $row['source'],
            'title'      => (string) $row['title'],
            'message'    => (string) $row['message'],
            'actor'      => is_array($row['actor']) ? $row['actor'] : [],
            'object'     => is_array($row['object']) ? $row['object'] : [],
            'created_at' => (string) $row['created_at'],
            'timestamp'  => strtotime((string) $row['created_at']) ?: 0,
            'dedupe_key' => (string) $row['dedupe_key'],
        ];
    }
}
