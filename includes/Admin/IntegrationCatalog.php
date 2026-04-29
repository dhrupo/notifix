<?php

namespace RTNotify\Admin;

final class IntegrationCatalog
{
    public function grouped(): array
    {
        $grouped = [];

        foreach ($this->definitions() as $definition) {
            $family = $definition['family'];
            unset($definition['family']);

            if (! isset($grouped[$family])) {
                $grouped[$family] = [];
            }

            $grouped[$family][] = $definition;
        }

        return $grouped;
    }

    private function definitions(): array
    {
        return [
            $this->item('Commerce', 'woocommerce', 'WooCommerce', class_exists('WooCommerce')),
            $this->item('Commerce', 'surecart', 'SureCart', defined('SURECART_APP_VERSION') || class_exists('\SureCart\SureCart')),
            $this->item('Commerce', 'fluentcart', 'FluentCart', defined('FLUENT_CART') || class_exists('\FluentCart\App\App')),
            $this->item('Commerce', 'edd', 'Easy Digital Downloads', defined('EDD_VERSION')),
            $this->item('Forms', 'fluentforms', 'Fluent Forms', defined('FLUENTFORM_VERSION')),
            $this->item('Forms', 'wpforms', 'WPForms', defined('WPFORMS_VERSION')),
            $this->item('Forms', 'gravityforms', 'Gravity Forms', class_exists('GFForms')),
            $this->item('Forms', 'ninjaforms', 'Ninja Forms', defined('NINJA_FORMS_VERSION')),
            $this->item('Forms', 'formidable', 'Formidable Forms', class_exists('FrmAppHelper')),
            $this->item('LMS', 'learndash', 'LearnDash', defined('LEARNDASH_VERSION')),
            $this->item('LMS', 'tutorlms', 'Tutor LMS', defined('TUTOR_VERSION')),
            $this->item('LMS', 'learnpress', 'LearnPress', defined('LEARNPRESS_VERSION')),
            $this->item('LMS', 'lifterlms', 'LifterLMS', defined('LLMS_VERSION')),
            $this->item('Membership', 'memberpress', 'MemberPress', class_exists('MeprTransaction')),
            $this->item('Membership', 'pmpro', 'Paid Memberships Pro', function_exists('pmpro_getLevel')),
            $this->item('Membership', 'restrict-content', 'Restrict Content', class_exists('RCP_Membership')),
            $this->item('Membership', 'paid-member-subscriptions', 'Paid Member Subscriptions', defined('PMS_VERSION')),
        ];
    }

    private function item(string $family, string $slug, string $label, bool $available): array
    {
        return [
            'family'    => $family,
            'slug'      => $slug,
            'label'     => $label,
            'available' => $available,
        ];
    }
}
