<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Login;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Forms\Login\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class CaptchaWidgetRendererTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('wp_enqueue_style')->justReturn(null);
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }

    public function testItPassesTheLoginContextToTheResolvedWidget(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );
        $renderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([$widget]),
        );

        $renderer->render(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            $this->settings(),
        );

        self::assertSame('wordpress_login', $widget->action);
        self::assertSame('loginform', $widget->formId);
    }

    public function testItDoesNothingWhenCaptchaIsDisabled(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );
        $renderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([$widget]),
        );

        $renderer->render(
            EffectiveCaptchaProvider::disabled(),
            $this->settings(),
        );

        self::assertNull($widget->action);
        self::assertNull($widget->formId);
    }

    public function testItGetsTheTokenFieldFromTheResolvedWidget(): void
    {
        $widget = new RecordingCaptchaWidget(
            CaptchaProvider::HCaptcha,
        );
        $renderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([$widget]),
        );

        self::assertSame(
            'recorded-token',
            $renderer->tokenFieldName(
                EffectiveCaptchaProvider::enabled(
                    CaptchaProvider::HCaptcha,
                ),
                $this->settings(),
            ),
        );
    }

    private function settings(): PluginSettings
    {
        return new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );
    }
}
