<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Login;

final class WooCommerceLoginFormRegistrar
{
    public function __construct(
        private readonly WooCommerceLoginFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'woocommerce_login_form',
            [$this->integration, 'render'],
        );

        add_filter(
            'woocommerce_process_login_errors',
            [$this->integration, 'validate'],
            10,
            3,
        );
    }
}
