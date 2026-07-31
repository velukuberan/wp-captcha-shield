<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Login;

final class LoginFormRegistrar
{
    public function __construct(
        private readonly LoginFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'login_enqueue_scripts',
            [$this->integration, 'enqueue'],
        );

        add_action(
            'login_form',
            [$this->integration, 'render'],
        );

        add_filter(
            'authenticate',
            [$this->integration, 'authenticate'],
            30,
            3,
        );
    }
}
