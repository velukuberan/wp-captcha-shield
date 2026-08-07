<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\LostPassword;

final class WooCommerceLostPasswordFormRegistrar
{
    public function __construct(
        private readonly WooCommerceLostPasswordFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'woocommerce_lostpassword_form',
            [$this->integration, 'render'],
        );

        add_action(
            'lostpassword_post',
            [$this->integration, 'validate'],
            30,
            2,
        );
    }
}
