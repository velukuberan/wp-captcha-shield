<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Login;

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaWidgetRenderer
{
    private const CAPTCHA_ACTION = 'wordpress_login';

    private const FORM_ID = 'loginform';

    public function __construct(
        private readonly CaptchaWidgetResolver $widgetResolver,
    ) {
    }

    public function enqueue(
        EffectiveCaptchaProvider $effectiveProvider,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return;
        }

        wp_enqueue_style(
            'wp-captcha-shield-login',
            WP_CAPTCHA_SHIELD_URL . 'assets/css/login.css',
            [],
            WP_CAPTCHA_SHIELD_VERSION,
        );

        $this->widgetResolver
            ->resolve($provider)
            ->enqueue($this->context(), $settings);
    }

    public function render(
        EffectiveCaptchaProvider $effectiveProvider,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return;
        }

        $this->widgetResolver
            ->resolve($provider)
            ->render($this->context(), $settings);
    }

    public function tokenFieldName(
        EffectiveCaptchaProvider $effectiveProvider,
        PluginSettings $settings,
    ): string {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return '';
        }

        return $this->widgetResolver
            ->resolve($provider)
            ->tokenFieldName($settings);
    }

    private function context(): CaptchaWidgetContext
    {
        return new CaptchaWidgetContext(
            self::CAPTCHA_ACTION,
            self::FORM_ID,
        );
    }
}
