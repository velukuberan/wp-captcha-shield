<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Login;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
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

        Functions\when('esc_attr')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }

    public function testItRendersTheNonInteractiveTurnstileWidget(): void
    {
        $settings = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            new TurnstileSettings(
                'turnstile-site-key',
                'turnstile-secret-key',
                CloudflareTurnstileMode::NonInteractive,
            ),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );

        ob_start();

        (new CaptchaWidgetRenderer())->render(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            $settings,
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString(
            'class="cf-turnstile"',
            $output,
        );
        self::assertStringContainsString(
            'data-sitekey="turnstile-site-key"',
            $output,
        );
        self::assertStringContainsString(
            'data-size="flexible"',
            $output,
        );
        self::assertStringContainsString(
            'data-appearance="always"',
            $output,
        );
        self::assertStringContainsString(
            'data-action="wordpress_login"',
            $output,
        );
    }
}
