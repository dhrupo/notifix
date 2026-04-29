<?php

namespace RTNotify\Abstracts;

abstract class AbstractFormAdapter extends AbstractHookAdapter
{
    protected function createFormEvent(string $type, array $actor, int $formId, string $formTitle, array $meta = []): array
    {
        return $this->createEvent($type, [
            'actor'  => $actor,
            'object' => $this->buildObject($formId, 'form', $formTitle),
            'meta'   => $meta,
        ]);
    }
}
