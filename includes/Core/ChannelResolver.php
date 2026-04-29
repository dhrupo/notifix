<?php

namespace RTNotify\Core;

final class ChannelResolver
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function channelName(): string
    {
        $configured = $this->sanitizeChannelName((string) $this->settings->get('channel_name', ''));

        if ($configured !== '') {
            return $configured;
        }

        $blogId = function_exists('get_current_blog_id') ? (int) get_current_blog_id() : 1;
        $hash = substr(md5((string) home_url('/')), 0, 12);

        return 'notifix-site-' . $blogId . '-' . $hash;
    }

    public function eventName(): string
    {
        $configured = $this->sanitizeEventName((string) $this->settings->get('transport_event_name', 'notifix.event'));

        return $configured !== '' ? $configured : 'notifix.event';
    }

    private function sanitizeChannelName(string $channel): string
    {
        return preg_replace('/[^A-Za-z0-9_\-=@,.;]/', '-', trim($channel)) ?: '';
    }

    private function sanitizeEventName(string $event): string
    {
        return preg_replace('/[^A-Za-z0-9_\-.:]/', '-', trim($event)) ?: '';
    }
}
