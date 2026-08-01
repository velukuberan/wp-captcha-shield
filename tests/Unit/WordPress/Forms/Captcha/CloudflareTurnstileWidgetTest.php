<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CloudflareTurnstileWidget;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class CloudflareTurnstileWidgetTest extends TestCase
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

    public function testItRendersTheLoginActionFromContext(): void
    {
        $widget = new CloudflareTurnstileWidget();

        ob_start();

        $widget->render(
            new CaptchaWidgetContext('custom_action', 'custom-form'),
            $this->settings(CloudflareTurnstileMode::Managed),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString(
            'data-action="custom_action"',
            $output,
        );
        self::assertStringContainsString(
            'data-sitekey="turnstile-site-key"',
            $output,
        );
    }

    public function testItPreservesNonInteractivePresentation(): void
    {
        $widget = new CloudflareTurnstileWidget();

        ob_start();

        $widget->render(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(CloudflareTurnstileMode::NonInteractive),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString(
            'data-appearance="always"',
            $output,
        );
        self::assertStringContainsString(
            'data-size="flexible"',
            $output,
        );
    }

    public function testItPreservesInvisiblePresentation(): void
    {
        $widget = new CloudflareTurnstileWidget();

        ob_start();

        $widget->render(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(CloudflareTurnstileMode::Invisible),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringNotContainsString(
            'wp-captcha-shield-widget',
            $output,
        );
        self::assertStringNotContainsString('data-size=', $output);
        self::assertStringNotContainsString('data-appearance=', $output);
    }

    private function settings(
        CloudflareTurnstileMode $mode,
    ): PluginSettings {
        return new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            new TurnstileSettings(
                'turnstile-site-key',
                'turnstile-secret-key',
                $mode,
            ),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );
    }
}
