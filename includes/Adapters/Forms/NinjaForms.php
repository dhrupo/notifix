<?php

namespace RTNotify\Adapters\Forms;

use RTNotify\Abstracts\AbstractFormAdapter;

final class NinjaForms extends AbstractFormAdapter
{
    public function getSlug(): string
    {
        return 'ninjaforms';
    }

    public function getLabel(): string
    {
        return 'Ninja Forms';
    }

    public function isPluginActive(): bool
    {
        return defined('NINJA_FORMS_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'ninja_forms_after_submission',
                'method'        => 'handleSubmission',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleSubmission($formData): void
    {
        $fields = $formData['fields'] ?? [];
        $name = '';

        foreach ($fields as $field) {
            if (($field['key'] ?? '') === 'firstname' || ($field['type'] ?? '') === 'firstname') {
                $name = (string) ($field['value'] ?? '');
                break;
            }
        }

        $this->emit($this->createFormEvent(
            'form_submit',
            $this->actorFromName($name),
            absint($formData['form_id'] ?? 0),
            (string) ($formData['settings']['title'] ?? __('a form', 'rt-notify')),
            ['submission_id' => absint($formData['actions']['save']['sub_id'] ?? 0)]
        ));
    }
}
