<?php

namespace RTNotify\Adapters\Forms;

use RTNotify\Abstracts\AbstractFormAdapter;

final class GravityForms extends AbstractFormAdapter
{
    public function getSlug(): string
    {
        return 'gravityforms';
    }

    public function getLabel(): string
    {
        return 'Gravity Forms';
    }

    public function isPluginActive(): bool
    {
        return class_exists('GFForms');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'gform_after_submission',
                'method'        => 'handleSubmission',
                'accepted_args' => 2,
            ],
        ];
    }

    public function handleSubmission($entry, $form): void
    {
        $name = '';

        if (is_array($entry)) {
            $name = $this->firstNonEmpty([
                $entry['1.3'] ?? '',
                $entry['1.6'] ?? '',
                $entry['1'] ?? '',
            ]);
        }

        $this->emit($this->createFormEvent(
            'form_submit',
            $this->actorFromName($name),
            absint($form['id'] ?? 0),
            (string) ($form['title'] ?? __('a form', 'rt-notify')),
            ['entry_id' => absint($entry['id'] ?? 0)]
        ));
    }
}
