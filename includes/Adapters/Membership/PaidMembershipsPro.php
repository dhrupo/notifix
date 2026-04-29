<?php

namespace RTNotify\Adapters\Membership;

use RTNotify\Abstracts\AbstractMembershipAdapter;

final class PaidMembershipsPro extends AbstractMembershipAdapter
{
    public function getSlug(): string
    {
        return 'pmpro';
    }

    public function getLabel(): string
    {
        return 'Paid Memberships Pro';
    }

    public function isPluginActive(): bool
    {
        return function_exists('pmpro_getLevel');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'pmpro_after_checkout',
                'method'        => 'handleCheckout',
                'accepted_args' => 2,
            ],
        ];
    }

    public function handleCheckout($userId, $order): void
    {
        $membershipId = absint($order->membership_id ?? 0);
        $level = $membershipId ? pmpro_getLevel($membershipId) : null;
        $label = $level->name ?? __('a membership', 'rt-notify');

        $this->emit($this->createMembershipEvent(
            'membership_started',
            $this->actorFromUserId(absint($userId)),
            $membershipId,
            $label,
            ['order_id' => absint($order->id ?? 0)]
        ));
    }
}
