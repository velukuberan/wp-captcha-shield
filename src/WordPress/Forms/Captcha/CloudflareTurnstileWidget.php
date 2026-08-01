<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CloudflareTurnstileWidget implements CaptchaWidget
{
    private const TOKEN_FIELD = 'cf-turnstile-response';

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::CloudflareTurnstile;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context, $settings);

        wp_enqueue_script(
            'wp-captcha-shield-turnstile',
            'https://challenges.cloudflare.com/turnstile/v0/api.js',
            [],
            null,
            true,
        );
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $turnstile = $settings->turnstile();

        if ($turnstile->mode() === CloudflareTurnstileMode::Invisible) {
            printf(
                '<div class="cf-turnstile" '
                . 'data-sitekey="%s" '
                . 'data-action="%s"></div>',
                esc_attr($turnstile->siteKey()),
                esc_attr($context->action()),
            );

            return;
        }

        if ($turnstile->mode() === CloudflareTurnstileMode::NonInteractive) {
            printf(
                '<div class="wp-captcha-shield-widget">'
                . '<div class="cf-turnstile" '
                . 'data-sitekey="%s" '
                . 'data-size="flexible" '
                . 'data-appearance="always" '
                . 'data-action="%s"></div>'
                . '</div>',
                esc_attr($turnstile->siteKey()),
                esc_attr($context->action()),
            );

            return;
        }

        printf(
            '<div class="wp-captcha-shield-widget">'
            . '<div class="cf-turnstile" '
            . 'data-sitekey="%s" '
            . 'data-size="flexible" '
            . 'data-action="%s"></div>'
            . '</div>',
            esc_attr($turnstile->siteKey()),
            esc_attr($context->action()),
        );
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        unset($settings);

        return self::TOKEN_FIELD;
    }
}
