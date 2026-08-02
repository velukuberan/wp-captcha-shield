<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class HCaptchaWidget implements CaptchaWidget
{
    private const TOKEN_FIELD = 'h-captcha-response';

    private const INVISIBLE_WIDGET_CLASS =
        'wp-captcha-shield-hcaptcha-invisible-widget';

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::HCaptcha;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context);

        if ($settings->hCaptcha()->mode() === HCaptchaDisplayMode::Invisible) {
            wp_enqueue_script(
                'wp-captcha-shield-hcaptcha-invisible',
                WP_CAPTCHA_SHIELD_URL . 'assets/js/hcaptcha-invisible.js',
                [],
                WP_CAPTCHA_SHIELD_VERSION,
                true,
            );

            wp_enqueue_script(
                'wp-captcha-shield-hcaptcha',
                'https://js.hcaptcha.com/1/api.js?'
                . 'onload=wpCaptchaShieldHCaptchaOnload&render=explicit',
                ['wp-captcha-shield-hcaptcha-invisible'],
                null,
                true,
            );

            return;
        }

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
        $hCaptcha = $settings->hCaptcha();

        if ($hCaptcha->mode() === HCaptchaDisplayMode::Invisible) {
            printf(
                '<div id="%s" class="%s" '
                . 'data-form-id="%s" data-site-key="%s"></div>',
                esc_attr($this->invisibleWidgetId($context)),
                esc_attr(self::INVISIBLE_WIDGET_CLASS),
                esc_attr($context->formId()),
                esc_attr($hCaptcha->siteKey()),
            );

            return;
        }

        printf(
            '<div class="h-captcha" data-sitekey="%s"></div>',
            esc_attr($hCaptcha->siteKey()),
        );
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        unset($settings);

        return self::TOKEN_FIELD;
    }

    private function invisibleWidgetId(
        CaptchaWidgetContext $context,
    ): string {
        return 'wp-captcha-shield-hcaptcha-invisible-widget-'
            . sanitize_html_class($context->formId());
    }
}
