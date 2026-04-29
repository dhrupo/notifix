<?php

namespace RTNotify\Transport;

use RTNotify\Abstracts\AbstractTransportDriver;
use RTNotify\Core\ChannelResolver;

final class PusherDriver extends AbstractTransportDriver
{
    private ChannelResolver $channelResolver;

    public function __construct($settings, ChannelResolver $channelResolver)
    {
        parent::__construct($settings);
        $this->channelResolver = $channelResolver;
    }

    public function getName(): string
    {
        return 'pusher';
    }

    public function isConfigured(): bool
    {
        return (string) $this->settings->get('pusher.app_id', '') !== ''
            && (string) $this->settings->get('pusher.key', '') !== ''
            && (string) $this->settings->get('pusher.secret', '') !== '';
    }

    public function dispatch(array $event): array
    {
        $appId = (string) $this->settings->get('pusher.app_id', '');
        $key = (string) $this->settings->get('pusher.key', '');
        $secret = (string) $this->settings->get('pusher.secret', '');
        $cluster = (string) $this->settings->get('pusher.cluster', 'mt1');

        if ($appId === '' || $key === '' || $secret === '') {
            return $this->failure('not_configured', 'Pusher credentials are incomplete.');
        }

        $body = wp_json_encode([
            'name'     => $this->channelResolver->eventName(),
            'channels' => [$this->channelResolver->channelName()],
            'data'     => wp_json_encode($event),
        ]);

        $path = '/apps/' . rawurlencode($appId) . '/events';
        $timestamp = time();
        $query = [
            'auth_key'       => $key,
            'auth_timestamp' => $timestamp,
            'auth_version'   => '1.0',
            'body_md5'       => md5((string) $body),
        ];

        ksort($query);
        $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        $stringToSign = "POST\n{$path}\n{$queryString}";
        $signature = hash_hmac('sha256', $stringToSign, $secret);
        $host = $cluster !== '' ? 'api-' . $cluster . '.pusher.com' : 'api.pusherapp.com';
        $url = 'https://' . $host . $path . '?' . $queryString . '&auth_signature=' . $signature;

        $response = wp_remote_post(
            $url,
            [
                'timeout' => 5,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body'    => $body,
            ]
        );

        if (is_wp_error($response)) {
            return $this->failure('dispatch_failed', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return $this->failure('dispatch_failed', 'Pusher responded with HTTP ' . $code . '.');
        }

        return $this->success();
    }
}
