<?php

namespace RTNotify\Transport;

use RTNotify\Abstracts\AbstractTransportDriver;

final class WebSocketDriver extends AbstractTransportDriver
{
    public function getName(): string
    {
        return 'websocket';
    }

    public function isConfigured(): bool
    {
        return (string) $this->settings->get('relay_endpoint', '') !== '';
    }

    public function dispatch(array $event): array
    {
        $endpoint = (string) $this->settings->get('relay_endpoint', '');
        $token = (string) $this->settings->get('auth_token', '');

        if ($endpoint === '') {
            return $this->failure('not_configured', 'Relay endpoint is empty.');
        }

        $response = wp_remote_post(
            $endpoint,
            [
                'timeout' => (int) $this->settings->get('connection_timeout', 5),
                'headers' => array_filter([
                    'Content-Type'  => 'application/json',
                    'Authorization' => $token !== '' ? 'Bearer ' . $token : '',
                ]),
                'body'    => wp_json_encode([
                    'channel' => 'rt-notify',
                    'event'   => $event,
                ]),
            ]
        );

        if (is_wp_error($response)) {
            return $this->failure('dispatch_failed', $response->get_error_message());
        }

        $code = (int) wp_remote_retrieve_response_code($response);

        if ($code < 200 || $code >= 300) {
            return $this->failure('dispatch_failed', 'Relay endpoint responded with HTTP ' . $code . '.');
        }

        return $this->success();
    }
}
