<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class HCaptchaWidget implements CaptchaWidget
{
    private const TOKEN_FIELD = 'h-captcha-response';

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::HCaptcha;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context, $settings);

        wp_enqueue_script(
            'wp-captcha-shield-hcaptcha',
            'https://js.hcaptcha.com/1/api.js',
            [],
            null,
            true,
        );
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context);

        printf(
            '<div class="h-captcha" data-sitekey="%s"></div>',
            esc_attr($settings->hCaptcha()->siteKey()),
        );
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        unset($settings);

        return self::TOKEN_FIELD;
    }
}
