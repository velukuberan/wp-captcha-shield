<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\WordPress\Settings\PluginSettings;

/**
 * One tab on the WP Captcha Shield settings page.
 */
interface SettingsTabSection
{
    public function slug(): string;

    public function label(): string;

    /**
     * Whether this tab's panel shows the "Save Changes" button.
     * Read-only tabs (e.g. Status) do not.
     */
    public function showsSubmitButton(): bool;

    public function render(PluginSettings $settings): void;
}
