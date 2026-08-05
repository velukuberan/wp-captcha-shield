<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Registration;

final class RegistrationFormRegistrar
{
    public function __construct(
        private readonly RegistrationFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'login_enqueue_scripts',
            [$this->integration, 'enqueue'],
        );

        add_action(
            'register_form',
            [$this->integration, 'render'],
        );

        add_filter(
            'registration_errors',
            [$this->integration, 'validate'],
            30,
            3,
        );
    }
}
