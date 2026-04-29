<?php

namespace RTNotify\Adapters\Forms;

use RTNotify\Abstracts\AbstractFormAdapter;

final class FormidableForms extends AbstractFormAdapter
{
    public function getSlug(): string
    {
        return 'formidable';
    }

    public function getLabel(): string
    {
        return 'Formidable Forms';
    }

    public function isPluginActive(): bool
    {
        return class_exists('FrmAppHelper');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'frm_after_create_entry',
                'method'        => 'handleSubmission',
                'accepted_args' => 2,
            ],
        ];
    }

    public function handleSubmission($entryId, $formId): void
    {
        $form = class_exists('FrmForm') && method_exists('FrmForm', 'getOne') ? \FrmForm::getOne($formId) : null;

        $this->emit($this->createFormEvent(
            'form_submit',
            $this->anonymousActor(),
            absint($formId),
            (string) ($form->name ?? __('a form', 'rt-notify')),
            ['entry_id' => absint($entryId)]
        ));
    }
}
