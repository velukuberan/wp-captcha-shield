<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Checkout;

final class WooCommerceClassicCheckoutRegistrar
{
    public function __construct(
        private readonly WooCommerceClassicCheckoutIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'woocommerce_review_order_before_submit',
            [$this->integration, 'render'],
        );
        add_action(
            'woocommerce_after_checkout_validation',
            [$this->integration, 'validate'],
            10,
            2,
        );
    }
}
