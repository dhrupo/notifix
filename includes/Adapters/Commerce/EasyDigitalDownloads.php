<?php

namespace RTNotify\Adapters\Commerce;

use RTNotify\Abstracts\AbstractCommerceAdapter;

final class EasyDigitalDownloads extends AbstractCommerceAdapter
{
    public function getSlug(): string
    {
        return 'edd';
    }

    public function getLabel(): string
    {
        return 'Easy Digital Downloads';
    }

    public function isPluginActive(): bool
    {
        return defined('EDD_VERSION');
    }

    protected function getHookDefinitions(): array
    {
        return [
            [
                'hook'          => 'edd_complete_purchase',
                'method'        => 'handleCompletedPurchase',
                'accepted_args' => 1,
            ],
        ];
    }

    public function handleCompletedPurchase($paymentId): void
    {
        $paymentId = absint($paymentId);

        if ($paymentId <= 0) {
            return;
        }

        $payment = function_exists('edd_get_payment') ? edd_get_payment($paymentId) : null;
        $downloads = function_exists('edd_get_payment_meta_cart_details') ? edd_get_payment_meta_cart_details($paymentId, true) : [];
        $firstItem = is_array($downloads) ? reset($downloads) : [];
        $productId = absint($firstItem['id'] ?? 0);
        $productName = $firstItem['name'] ?? get_the_title($productId);
        $customerName = '';

        if ($payment && property_exists($payment, 'email')) {
            $customerName = trim((string) $payment->email);
        }

        $this->emit($this->createCommerceEvent(
            'purchase',
            $this->actorFromName($customerName),
            $productId,
            $productName ?: __('a download', 'rt-notify'),
            [
                'payment_id' => $paymentId,
            ]
        ));
    }
}
