<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaWidgetRenderer
{
    public function __construct(
        private readonly CaptchaWidgetResolver $widgetResolver,
    ) {
    }

    public function enqueue(
        EffectiveCaptchaProvider $effectiveProvider,
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return;
        }

        $this->widgetResolver
            ->resolve($provider)
            ->enqueue($context, $settings);
    }

    public function render(
        EffectiveCaptchaProvider $effectiveProvider,
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return;
        }

        $this->widgetResolver
            ->resolve($provider)
            ->render($context, $settings);
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
}
