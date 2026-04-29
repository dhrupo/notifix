<?php

namespace RTNotify\Adapters\Forms;

use RTNotify\Abstracts\AbstractFormAdapter;

final class FluentForms extends AbstractFormAdapter
{
    public function getSlug(): string
    {
        return 'fluentforms';
    }

    public function getLabel(): string
    {
        return 'Fluent Forms';
    }

    public function isPluginActive(): bool
    {
        return defined('FLUENTFORM_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'fluentform_submission_inserted',
                'method'        => 'handleSubmission',
                'accepted_args' => 3,
            ],
        ];
    }

    public function handleSubmission($entryId, $formData, $form): void
    {
        $formId = absint($form->id ?? $form['id'] ?? 0);
        $formTitle = (string) ($form->title ?? $form['title'] ?? __('a form', 'rt-notify'));
        $name = is_array($formData) ? $this->firstNonEmpty([$formData['names']['first_name'] ?? '', $formData['full_name'] ?? '', $formData['name'] ?? '']) : '';

        $this->emit($this->createFormEvent(
            'form_submit',
            $this->actorFromName($name),
            $formId,
            $formTitle,
            ['entry_id' => absint($entryId)]
        ));
    }
}
