<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Checkout;

final class WooCommerceBlockCheckoutRegistrar
{
    public function __construct(
        private readonly WooCommerceBlockCheckoutIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_filter(
            'render_block_woocommerce/checkout-actions-block',
            [$this->integration, 'render'],
        );
        add_filter(
            'rest_pre_dispatch',
            [$this->integration, 'validate'],
            10,
            3,
        );
    }
}
