<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce;

final class WooCommerceBootstrap
{
    public function __construct(
        private readonly WooCommerceAvailability $availability,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'plugins_loaded',
            [$this, 'initialize'],
        );
    }

    public function initialize(): void
    {
        if (!$this->availability->isAvailable()) {
            return;
        }

        /*
         * WooCommerce form integrations will be initialized here by their
         * respective implementation tickets.
         */
    }
}
