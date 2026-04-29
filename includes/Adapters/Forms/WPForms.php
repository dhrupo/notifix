<?php

namespace RTNotify\Adapters\Forms;

use RTNotify\Abstracts\AbstractFormAdapter;

final class WPForms extends AbstractFormAdapter
{
    public function getSlug(): string
    {
        return 'wpforms';
    }

    public function getLabel(): string
    {
        return 'WPForms';
    }

    public function isPluginActive(): bool
    {
        return defined('WPFORMS_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'wpforms_process_complete',
                'method'        => 'handleSubmission',
                'accepted_args' => 4,
            ],
        ];
    }

    public function handleSubmission($fields, $entry, $formData, $entryId): void
    {
        $name = '';

        if (is_array($fields)) {
            foreach ($fields as $field) {
                if (! empty($field['name']) && in_array($field['name'], ['Name', 'name', 'Full Name'], true)) {
                    $name = (string) ($field['value'] ?? '');
                    break;
                }
            }
        }

        $this->emit($this->createFormEvent(
            'form_submit',
            $this->actorFromName($name),
            absint($formData['id'] ?? 0),
            (string) ($formData['settings']['form_title'] ?? __('a form', 'rt-notify')),
            ['entry_id' => absint($entryId)]
        ));
    }
}
