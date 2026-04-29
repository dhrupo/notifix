<?php

namespace RTNotify\Core;

final class TemplateRenderer
{
    private Settings $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function renderForStorage(array $event): string
    {
        $template = (string) $this->settings->get('templates.' . $event['type'], '{actor_name} {object_name}');

        return trim($this->replaceTokens($template, $this->tokens($event, false)));
    }

    public function resolveActorName(array $event): string
    {
        $fallback = (string) $this->settings->get('identity.fallback_label', 'Someone');
        $showUsername = $this->settings->get('identity.show_username', 'yes') === 'yes';
        $maskMode = (string) $this->settings->get('identity.mask_mode', 'safe');

        $username = trim((string) ($event['actor']['username'] ?? ''));
        $label = trim((string) ($event['actor']['label'] ?? ''));

        if (! $showUsername || $maskMode === 'always-anonymous') {
            return $fallback;
        }

        if ($username !== '') {
            return $username;
        }

        if ($label !== '' && $label !== $fallback) {
            return $label;
        }

        return $fallback;
    }

    public function tokens(array $event, bool $withTime): array
    {
        $objectName = trim((string) ($event['object']['label'] ?? ''));

        return [
            '{actor_name}'     => $this->resolveActorName($event),
            '{object_name}'    => $objectName !== '' ? $objectName : __('this item', 'rt-notify'),
            '{event_time_ago}' => $withTime ? '{event_time_ago}' : '',
            '{actor_location}' => trim((string) ($event['actor']['location'] ?? '')),
            '{source}'         => trim((string) ($event['source'] ?? '')),
            '{event_type}'     => trim((string) ($event['type'] ?? '')),
        ];
    }

    private function replaceTokens(string $template, array $tokens): string
    {
        $rendered = strtr($template, $tokens);
        $rendered = preg_replace('/\s+/', ' ', (string) $rendered);

        return trim((string) $rendered);
    }
}
