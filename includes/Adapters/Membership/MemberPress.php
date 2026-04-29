<?php

namespace RTNotify\Adapters\Membership;

use RTNotify\Abstracts\AbstractMembershipAdapter;

final class MemberPress extends AbstractMembershipAdapter
{
    public function getSlug(): string
    {
        return 'memberpress';
    }

    public function getLabel(): string
    {
        return 'MemberPress';
    }

    public function isPluginActive(): bool
    {
        return class_exists('MeprTransaction');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'mepr-event-transaction-completed',
                'method'        => 'handleTransactionCompleted',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleTransactionCompleted($event): void
    {
        if (! is_object($event) || empty($event->txn)) {
            return;
        }

        $transaction = $event->txn;
        $productId = absint($transaction->product_id ?? 0);
        $userId = absint($transaction->user_id ?? 0);

        $this->emit($this->createMembershipEvent(
            'membership_started',
            $this->actorFromUserId($userId),
            $productId,
            get_the_title($productId) ?: __('a membership', 'rt-notify'),
            ['transaction_id' => absint($transaction->id ?? 0)]
        ));
    }
}
