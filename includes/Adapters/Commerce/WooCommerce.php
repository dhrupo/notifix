<?php

namespace RTNotify\Adapters\Commerce;

use RTNotify\Abstracts\AbstractCommerceAdapter;

final class WooCommerce extends AbstractCommerceAdapter
{
    public function getSlug(): string
    {
        return 'woocommerce';
    }

    public function getLabel(): string
    {
        return 'WooCommerce';
    }

    public function isPluginActive(): bool
    {
        return class_exists('WooCommerce');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'woocommerce_payment_complete',
                'method'        => 'handlePaymentComplete',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handlePaymentComplete($orderId): void
    {
        if (! function_exists('wc_get_order')) {
            return;
        }

        $order = wc_get_order($orderId);

        if (! $order) {
            return;
        }

        $items = $order->get_items();
        $item = $items ? reset($items) : null;
        $productName = $item ? $item->get_name() : __('a product', 'rt-notify');
        $actorName = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());

        $this->emit($this->createCommerceEvent(
            'purchase',
            $this->actorFromName($actorName),
            $item && $item->get_product_id() ? (int) $item->get_product_id() : 0,
            $productName,
            [
                'order_id' => (int) $orderId,
                'total'    => $order->get_total(),
                'currency' => $order->get_currency(),
            ]
        ));
    }
}
