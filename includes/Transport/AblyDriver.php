<?php

namespace RTNotify\Transport;

use RTNotify\Abstracts\AbstractTransportDriver;
use RTNotify\Core\ChannelResolver;

final class AblyDriver extends AbstractTransportDriver
{
    private ChannelResolver $channelResolver;

    public function __construct($settings, ChannelResolver $channelResolver)
    {
        parent::__construct($settings);
        $this->channelResolver = $channelResolver;
    }

    public function getName(): string
    {
        return 'ably';
    }

    public function isConfigured(): bool
    {
        return (string) $this->settings->get('ably.api_key', '') !== '';
    }

    public function dispatch(array $event): array
    {
        $apiKey = (string) $this->settings->get('ably.api_key', '');

        if ($apiKey === '') {
            return $this->failure('not_configured', 'Ably API key is empty.');
        }

        $channel = rawurlencode($this->channelResolver->channelName());
        $url = 'https://rest.ably.io/channels/' . $channel . '/messages';
        $messageId = substr((string) ($event['dedupe_key'] ?? md5(wp_json_encode($event))), 0, 64);
        $body = wp_json_encode([
            [
                'name' => $this->channelResolver->eventName(),
                'data' => $event,
                'id'   => $messageId,
            ],
        ]);

        $response = wp_remote_post(
            $url,
            [
                'timeout' => (int) $this->settings->get('connection_timeout', 5),
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Basic ' . base64_encode($apiKey),
                ],
                'body'    => $body,
            ]
        );

        if (is_wp_error($response)) {
            return $this->failure('dispatch_failed', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return $this->failure('dispatch_failed', 'Ably responded with HTTP ' . $code . '.');
        }

        return $this->success();
    }
}
