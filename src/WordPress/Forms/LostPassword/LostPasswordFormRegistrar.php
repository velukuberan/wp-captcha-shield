<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\LostPassword;

final class LostPasswordFormRegistrar
{
    public function __construct(
        private readonly LostPasswordFormIntegration $integration,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'login_enqueue_scripts',
            [$this->integration, 'enqueue'],
        );

        add_action(
            'lostpassword_form',
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
