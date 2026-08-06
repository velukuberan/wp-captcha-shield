<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Registration;

final class WooCommerceRegistrationFormRegistrar
{
    public function __construct(
        private readonly WooCommerceRegistrationFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('woocommerce_register_form', [$this->integration, 'render']);
        add_filter('woocommerce_registration_errors', [$this->integration, 'validate'], 10, 3);
    }
}
