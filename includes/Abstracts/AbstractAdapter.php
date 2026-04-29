<?php

namespace RTNotify\Abstracts;

use RTNotify\Contracts\AdapterInterface;
use RTNotify\Core\EventManager;
use RTNotify\Core\Settings;
use WP_User;

abstract class AbstractAdapter implements AdapterInterface
{
    protected EventManager $eventManager;

    protected Settings $settings;

    public function __construct(EventManager $eventManager, Settings $settings)
    {
        $this->eventManager = $eventManager;
        $this->settings = $settings;
    }

    public function shouldBoot(): bool
    {
        return $this->isPluginActive() && $this->settings->isIntegrationEnabled($this->getSlug());
    }

    protected function emit(array $event): void
    {
        if (empty($event['source'])) {
            $event['source'] = $this->getSlug();
        }

        $this->eventManager->emit($event);
    }

    protected function actorFromUserId(int $userId = 0): array
    {
        if ($userId <= 0) {
            return $this->anonymousActor();
        }

        $user = get_user_by('id', $userId);

        if (! $user instanceof WP_User) {
            return $this->anonymousActor();
        }

        $username = $this->firstNonEmpty([
            $user->display_name,
            trim($user->first_name . ' ' . $user->last_name),
            $user->user_nicename,
            $user->user_login,
        ]);

        return [
            'label'    => $username ?: $this->settings->get('identity.fallback_label', 'Someone'),
            'username' => $username ?: '',
            'location' => '',
        ];
    }

    protected function actorFromName(?string $name): array
    {
        $name = $this->sanitizeText($name);

        if ($name === '') {
            return $this->anonymousActor();
        }

        return [
            'label'    => $name,
            'username' => $name,
            'location' => '',
        ];
    }

    protected function anonymousActor(): array
    {
        return [
            'label'    => $this->settings->get('identity.fallback_label', 'Someone'),
            'username' => '',
            'location' => '',
        ];
    }

    protected function buildObject(int $id, string $type, ?string $label): array
    {
        return [
            'id'    => $id,
            'type'  => sanitize_key($type),
            'label' => $this->sanitizeText($label),
        ];
    }

    protected function sanitizeText(?string $value): string
    {
        return sanitize_text_field(wp_strip_all_tags((string) $value));
    }

    protected function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    protected function createEvent(string $type, array $event = []): array
    {
        $defaults = [
            'type'       => $type,
            'title'      => '',
            'message'    => '',
            'meta'       => [],
            'actor'      => $this->anonymousActor(),
            'object'     => $this->buildObject(0, 'item', ''),
            'visibility' => 'public',
            'source'     => $this->getSlug(),
        ];

        return array_replace_recursive($defaults, $event);
    }
}
