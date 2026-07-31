<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

final class SettingsPageRegistrar
{
    public function __construct(
        private readonly SettingsPage $settingsPage,
    ) {
    }

    public function registerHooks(): void
    {
        add_action(
            'admin_menu',
            [$this->settingsPage, 'register'],
        );

        add_action(
            'admin_post_' . SettingsPage::SAVE_ACTION,
            [$this->settingsPage, 'save'],
        );
    }
}
