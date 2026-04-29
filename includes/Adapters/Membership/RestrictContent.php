<?php

namespace RTNotify\Adapters\Membership;

use RTNotify\Abstracts\AbstractMembershipAdapter;

final class RestrictContent extends AbstractMembershipAdapter
{
    public function getSlug(): string
    {
        return 'restrict-content';
    }

    public function getLabel(): string
    {
        return 'Restrict Content';
    }

    public function isPluginActive(): bool
    {
        return class_exists('RCP_Membership');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'rcp_membership_post_activate',
                'method'        => 'handleMembershipActivated',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleMembershipActivated($membershipId): void
    {
        if (! class_exists('RCP_Membership')) {
            return;
        }

        $membership = new \RCP_Membership($membershipId);
        $levelId = absint($membership->get_object_id());
        $userId = absint($membership->get_customer_id());

        $this->emit($this->createMembershipEvent(
            'membership_started',
            $this->actorFromUserId($userId),
            $levelId,
            get_the_title($levelId) ?: __('a membership', 'rt-notify')
        ));
    }
}
