<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms;

use WpCaptchaShield\WordPress\Forms\Comments\CommentFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormRegistrar;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Registration\RegistrationFormRegistrar;

/**
 * Registers CAPTCHA protection for the core WordPress forms (login,
 * registration, lost password, comments). Unlike WooCommerceBootstrap,
 * these forms are always available, so hooks are registered immediately
 * rather than deferred to `plugins_loaded`.
 */
final class WordPressFormsBootstrap
{
    public function __construct(
        private readonly LoginFormRegistrar $loginFormRegistrar,
        private readonly RegistrationFormRegistrar $registrationFormRegistrar,
        private readonly LostPasswordFormRegistrar $lostPasswordFormRegistrar,
        private readonly CommentFormRegistrar $commentFormRegistrar,
    ) {
    }

    public function registerHooks(): void
    {
        $this->loginFormRegistrar->registerHooks();
        $this->registrationFormRegistrar->registerHooks();
        $this->lostPasswordFormRegistrar->registerHooks();
        $this->commentFormRegistrar->registerHooks();
    }
}
