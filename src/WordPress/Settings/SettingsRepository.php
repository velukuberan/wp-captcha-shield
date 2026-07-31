<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

interface SettingsRepository
{
    public function load(): PluginSettings;

    public function save(PluginSettings $settings): void;
}
