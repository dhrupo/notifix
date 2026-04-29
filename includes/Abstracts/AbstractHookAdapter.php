<?php

namespace RTNotify\Abstracts;

abstract class AbstractHookAdapter extends AbstractAdapter
{
    public function boot(): void
    {
        foreach ($this->getHookDefinitions() as $definition) {
            add_action(
                $definition['hook'],
                [$this, $definition['method']],
                (int) ($definition['priority'] ?? 10),
                (int) ($definition['accepted_args'] ?? 1)
            );
        }
    }

    /**
     * @return array<int, array<string, int|string>>
     */
    abstract protected function getHookDefinitions(): array;
}
