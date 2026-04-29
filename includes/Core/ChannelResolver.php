<?php

namespace RTNotify\Core;

final class ChannelResolver
{
    public function channelName(): string
    {
        $blogId = function_exists('get_current_blog_id') ? (int) get_current_blog_id() : 1;
        $hash = substr(md5($this->siteFingerprint()), 0, 12);

        return 'notifix-site-' . $blogId . '-' . $hash;
    }

    public function eventName(): string
    {
        return 'notifix.event';
    }

    private function siteFingerprint(): string
    {
        $url = (string) home_url('/');
        $parts = wp_parse_url($url);

        $host = isset($parts['host']) ? strtolower((string) $parts['host']) : '';
        $port = isset($parts['port']) ? ':' . (string) $parts['port'] : '';
        $path = isset($parts['path']) ? trim((string) $parts['path'], '/') : '';

        return $host . $port . '/' . $path;
    }
}
