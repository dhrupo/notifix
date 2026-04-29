<?php

namespace RTNotify\Adapters\Membership;

use RTNotify\Abstracts\AbstractMembershipAdapter;

final class PaidMemberSubscriptions extends AbstractMembershipAdapter
{
    public function getSlug(): string
    {
        return 'paid-member-subscriptions';
    }

    public function getLabel(): string
    {
        return 'Paid Member Subscriptions';
    }

    public function isPluginActive(): bool
    {
        return defined('PMS_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'pms_member_subscription_inserted',
                'method'        => 'handleSubscriptionInserted',
                'accepted_args' => 2,
            ],
        ];
    }

    public function handleSubscriptionInserted($subscriptionId, $data): void
    {
        $planId = absint($data['subscription_plan_id'] ?? 0);
        $userId = absint($data['user_id'] ?? 0);

        $this->emit($this->createMembershipEvent(
            'membership_started',
            $this->actorFromUserId($userId),
            $planId,
            get_the_title($planId) ?: __('a membership', 'rt-notify'),
            ['subscription_id' => absint($subscriptionId)]
        ));
    }
}
