<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaWidgetRendererTest extends TestCase
{
    public function testItEnqueuesTheResolvedWidgetWithTheProvidedContext(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );

        $renderer = $this->renderer($widget);

        $renderer->enqueue(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            new CaptchaWidgetContext(
                'wordpress_login',
                'loginform',
            ),
            PluginSettings::defaults(),
        );

        self::assertSame('enqueue', $widget->operation);
        self::assertSame('wordpress_login', $widget->action);
        self::assertSame('loginform', $widget->formId);
    }

    public function testItDoesNotEnqueueWhenCaptchaIsDisabled(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );

        $renderer = $this->renderer($widget);

        $renderer->enqueue(
            EffectiveCaptchaProvider::disabled(),
            new CaptchaWidgetContext(
                'wordpress_login',
                'loginform',
            ),
            PluginSettings::defaults(),
        );

        self::assertNull($widget->operation);
        self::assertNull($widget->action);
        self::assertNull($widget->formId);
    }

    public function testItRendersTheResolvedWidgetWithTheProvidedContext(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );

        $renderer = $this->renderer($widget);

        $renderer->render(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            new CaptchaWidgetContext(
                'registration',
                'registerform',
            ),
            PluginSettings::defaults(),
        );

        self::assertSame('render', $widget->operation);
        self::assertSame('registration', $widget->action);
        self::assertSame('registerform', $widget->formId);
    }

    public function testItDoesNotRenderWhenCaptchaIsDisabled(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );

        $renderer = $this->renderer($widget);

        $renderer->render(
            EffectiveCaptchaProvider::disabled(),
            new CaptchaWidgetContext(
                'registration',
                'registerform',
            ),
            PluginSettings::defaults(),
        );

        self::assertNull($widget->operation);
        self::assertNull($widget->action);
        self::assertNull($widget->formId);
    }

    public function testItGetsTheTokenFieldFromTheResolvedWidget(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::HCaptcha,
        );

        $renderer = $this->renderer($widget);

        $fieldName = $renderer->tokenFieldName(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::HCaptcha,
            ),
            PluginSettings::defaults(),
        );

        self::assertSame('tokenFieldName', $widget->operation);
        self::assertSame('recorded-token', $fieldName);
    }

    public function testItReturnsAnEmptyTokenFieldWhenCaptchaIsDisabled(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::HCaptcha,
        );

        $renderer = $this->renderer($widget);

        $fieldName = $renderer->tokenFieldName(
            EffectiveCaptchaProvider::disabled(),
            PluginSettings::defaults(),
        );

        self::assertSame('', $fieldName);
        self::assertNull($widget->operation);
    }

    private function renderer(
        RecordingCaptchaWidget $widget,
    ): CaptchaWidgetRenderer {
        return new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([$widget]),
        );
    }
}
