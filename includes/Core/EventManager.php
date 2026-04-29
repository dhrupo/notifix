<?php

namespace RTNotify\Core;

final class EventManager
{
    private EventNormalizer $normalizer;

    private EventRepository $repository;

    private NotificationPolicy $policy;

    private TransportManager $transportManager;

    private TemplateRenderer $templateRenderer;

    public function __construct(
        EventNormalizer $normalizer,
        EventRepository $repository,
        NotificationPolicy $policy,
        TransportManager $transportManager,
        TemplateRenderer $templateRenderer
    ) {
        $this->normalizer = $normalizer;
        $this->repository = $repository;
        $this->policy = $policy;
        $this->transportManager = $transportManager;
        $this->templateRenderer = $templateRenderer;
    }

    public function emit(array $event): ?int
    {
        try {
            $event = $this->normalizer->normalize($event);
        } catch (\InvalidArgumentException $exception) {
            return null;
        }

        if (! $this->policy->shouldProcess($event)) {
            return null;
        }

        if ($event['title'] === '') {
            $event['title'] = $this->templateRenderer->renderForStorage($event);
        }

        if ($event['message'] === '') {
            $event['message'] = $event['title'];
        }

        $eventId = $this->repository->insert($event);
        $event['id'] = $eventId;

        $result = $this->transportManager->dispatch($event);
        $this->repository->updateDispatchStatus(
            $eventId,
            (string) ($result['status'] ?? 'failed'),
            (string) ($result['error'] ?? '')
        );

        return $eventId;
    }
}
