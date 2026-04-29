<?php

namespace RTNotify\Core;

use RTNotify\Adapters\Commerce\EasyDigitalDownloads;
use RTNotify\Adapters\Commerce\FluentCart;
use RTNotify\Adapters\Commerce\SureCart;
use RTNotify\Adapters\Commerce\WooCommerce;
use RTNotify\Adapters\Forms\FluentForms;
use RTNotify\Adapters\Forms\FormidableForms;
use RTNotify\Adapters\Forms\GravityForms;
use RTNotify\Adapters\Forms\NinjaForms;
use RTNotify\Adapters\Forms\WPForms;
use RTNotify\Adapters\LMS\LearnDash;
use RTNotify\Adapters\LMS\LearnPress;
use RTNotify\Adapters\LMS\LifterLMS;
use RTNotify\Adapters\LMS\TutorLMS;
use RTNotify\Adapters\Membership\MemberPress;
use RTNotify\Adapters\Membership\PaidMemberSubscriptions;
use RTNotify\Adapters\Membership\PaidMembershipsPro;
use RTNotify\Adapters\Membership\RestrictContent;
use RTNotify\Contracts\AdapterInterface;

final class AdapterRegistry
{
    private EventManager $eventManager;

    private Settings $settings;

    public function __construct(EventManager $eventManager, Settings $settings)
    {
        $this->eventManager = $eventManager;
        $this->settings = $settings;
    }

    public function boot(): void
    {
        foreach ($this->adapters() as $adapter) {
            if ($adapter->shouldBoot()) {
                $adapter->boot();
            }
        }
    }

    /**
     * @return array<int, AdapterInterface>
     */
    private function adapters(): array
    {
        return [
            new WooCommerce($this->eventManager, $this->settings),
            new SureCart($this->eventManager, $this->settings),
            new FluentCart($this->eventManager, $this->settings),
            new EasyDigitalDownloads($this->eventManager, $this->settings),
            new FluentForms($this->eventManager, $this->settings),
            new WPForms($this->eventManager, $this->settings),
            new GravityForms($this->eventManager, $this->settings),
            new NinjaForms($this->eventManager, $this->settings),
            new FormidableForms($this->eventManager, $this->settings),
            new LearnDash($this->eventManager, $this->settings),
            new TutorLMS($this->eventManager, $this->settings),
            new LearnPress($this->eventManager, $this->settings),
            new LifterLMS($this->eventManager, $this->settings),
            new MemberPress($this->eventManager, $this->settings),
            new PaidMembershipsPro($this->eventManager, $this->settings),
            new RestrictContent($this->eventManager, $this->settings),
            new PaidMemberSubscriptions($this->eventManager, $this->settings),
        ];
    }
}
