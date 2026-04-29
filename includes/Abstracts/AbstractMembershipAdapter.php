<?php

namespace RTNotify\Abstracts;

abstract class AbstractMembershipAdapter extends AbstractHookAdapter
{
    protected function createMembershipEvent(string $type, array $actor, int $membershipId, string $membershipLabel, array $meta = []): array
    {
        return $this->createEvent($type, [
            'actor'  => $actor,
            'object' => $this->buildObject($membershipId, 'membership', $membershipLabel),
            'meta'   => $meta,
        ]);
    }
}
