<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

interface CaptchaWidget
{
    public function provider(): CaptchaProvider;

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void;

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void;

    public function tokenFieldName(PluginSettings $settings): string;
}
