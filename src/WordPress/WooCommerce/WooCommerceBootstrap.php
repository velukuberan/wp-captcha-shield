<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce;

use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\LostPassword\WooCommerceLostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormRegistrar;

final class WooCommerceBootstrap
{
    public function __construct(
        private readonly WooCommerceAvailability $availability,
        private readonly WooCommerceLoginFormRegistrar $loginFormRegistrar,
        private readonly WooCommerceRegistrationFormRegistrar $registrationFormRegistrar,
        private readonly WooCommerceLostPasswordFormRegistrar $lostPasswordFormRegistrar,
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
    }
}
