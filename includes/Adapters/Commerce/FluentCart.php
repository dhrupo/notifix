<?php

namespace RTNotify\Adapters\Commerce;

use RTNotify\Abstracts\AbstractCommerceAdapter;

final class FluentCart extends AbstractCommerceAdapter
{
    public function getSlug(): string
    {
        return 'fluentcart';
    }

    public function getLabel(): string
    {
        return 'FluentCart';
    }

    public function isPluginActive(): bool
    {
        return defined('FLUENT_CART') || class_exists('\FluentCart\App\App');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'fluent_cart/order_paid_done',
                'method'        => 'handleOrderPaid',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleOrderPaid($order): void
    {
        $orderId = 0;
        $customerName = '';
        $productName = '';
        $productId = 0;

        if (is_object($order)) {
            $orderId = absint($order->id ?? 0);
            $customerName = trim((string) ($order->customer_name ?? ''));
            $items = $order->items ?? [];
            $firstItem = is_array($items) ? reset($items) : null;
            $productName = is_object($firstItem) ? (string) ($firstItem->title ?? '') : '';
            $productId = is_object($firstItem) ? absint($firstItem->product_id ?? 0) : 0;
        }

        if ($productName === '') {
            $productName = __('a product', 'rt-notify');
        }

        $this->emit($this->createCommerceEvent(
            'purchase',
            $this->actorFromName($customerName),
            $productId,
            $productName,
            [
                'order_id' => $orderId,
            ]
        ));
    }
}
