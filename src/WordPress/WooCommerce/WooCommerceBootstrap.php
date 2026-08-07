<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce;

use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceClassicCheckoutRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\LostPassword\WooCommerceLostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\ProductReviews\WooCommerceProductReviewFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormRegistrar;

final class WooCommerceBootstrap
{
    public function __construct(
        private readonly WooCommerceAvailability $availability,
        private readonly WooCommerceLoginFormRegistrar $loginFormRegistrar,
        private readonly WooCommerceRegistrationFormRegistrar $registrationFormRegistrar,
        private readonly WooCommerceLostPasswordFormRegistrar $lostPasswordFormRegistrar,
        private readonly WooCommerceProductReviewFormRegistrar $productReviewFormRegistrar,
        private readonly WooCommerceClassicCheckoutRegistrar $classicCheckoutRegistrar,
        private readonly WooCommerceBlockCheckoutRegistrar $blockCheckoutRegistrar,
    ) {
    }

    public function registerHooks(): void
    {
        add_action('plugins_loaded', [$this, 'initialize']);
    }

    public function initialize(): void
    {
        if (!$this->availability->isAvailable()) {
            return;
        }

        $this->loginFormRegistrar->registerHooks();
        $this->registrationFormRegistrar->registerHooks();
        $this->lostPasswordFormRegistrar->registerHooks();
        $this->productReviewFormRegistrar->registerHooks();
        $this->classicCheckoutRegistrar->registerHooks();
        $this->blockCheckoutRegistrar->registerHooks();
    }
}
