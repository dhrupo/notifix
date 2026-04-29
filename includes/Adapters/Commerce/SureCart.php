<?php

namespace RTNotify\Adapters\Commerce;

use RTNotify\Abstracts\AbstractCommerceAdapter;

final class SureCart extends AbstractCommerceAdapter
{
    public function getSlug(): string
    {
        return 'surecart';
    }

    public function getLabel(): string
    {
        return 'SureCart';
    }

    public function isPluginActive(): bool
    {
        return defined('SURECART_APP_VERSION') || class_exists('\SureCart\SureCart');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'surecart/order_paid',
                'method'        => 'handleOrderPaid',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleOrderPaid($order): void
    {
        $productName = '';
        $productId = 0;
        $customerName = '';
        $orderId = 0;

        if (is_object($order)) {
            $orderId = absint($order->id ?? 0);
            $customerName = trim((string) ($order->customer_name ?? ''));
            $lineItems = $order->line_items ?? [];
            $firstItem = is_array($lineItems) ? reset($lineItems) : null;
            $productName = is_object($firstItem) ? (string) ($firstItem->price_name ?? $firstItem->product_name ?? '') : '';
            $productId = is_object($firstItem) ? absint($firstItem->product_id ?? 0) : 0;
        } elseif (is_array($order)) {
            $orderId = absint($order['id'] ?? 0);
            $customerName = trim((string) ($order['customer_name'] ?? ''));
            $firstItem = isset($order['line_items'][0]) ? $order['line_items'][0] : [];
            $productName = (string) ($firstItem['price_name'] ?? $firstItem['product_name'] ?? '');
            $productId = absint($firstItem['product_id'] ?? 0);
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
