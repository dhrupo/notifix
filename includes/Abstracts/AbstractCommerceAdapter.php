<?php

namespace RTNotify\Abstracts;

abstract class AbstractCommerceAdapter extends AbstractHookAdapter
{
    protected function createCommerceEvent(string $type, array $actor, int $objectId, string $objectLabel, array $meta = []): array
    {
        return $this->createEvent($type, [
            'actor'  => $actor,
            'object' => $this->buildObject($objectId, 'product', $objectLabel),
            'meta'   => $meta,
        ]);
    }
}
