<?php

namespace RTNotify\Core;

use WP_REST_Request;
use WP_REST_Response;

final class RestController
{
    private EventRepository $repository;

    public function __construct(EventRepository $repository)
    {
        $this->repository = $repository;
    }

    public function registerRoutes(): void
    {
        register_rest_route(
            'notifix/v1',
            '/events',
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'events'],
                'permission_callback' => '__return_true',
                'args'                => [
                    'since_id' => [
                        'sanitize_callback' => 'absint',
                        'default'           => 0,
                    ],
                    'limit'    => [
                        'sanitize_callback' => 'absint',
                        'default'           => 5,
                    ],
                ],
            ]
        );
    }

    public function events(WP_REST_Request $request): WP_REST_Response
    {
        $limit = max(1, min(20, (int) $request->get_param('limit')));
        $sinceId = max(0, (int) $request->get_param('since_id'));
        $events = $this->repository->publicFeed($sinceId, $limit);
        $lastId = 0;

        if (! empty($events)) {
            $last = end($events);
            $lastId = (int) ($last['id'] ?? 0);
        }

        return new WP_REST_Response([
            'events'   => array_values($events),
            'last_id'  => $lastId,
            'limit'    => $limit,
            'since_id' => $sinceId,
        ]);
    }
}
