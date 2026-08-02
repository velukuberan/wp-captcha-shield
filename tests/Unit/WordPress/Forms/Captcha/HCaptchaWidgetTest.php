<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\HCaptchaWidget;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class HCaptchaWidgetTest extends TestCase
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

    public function testItReportsHCaptchaAsItsProvider(): void
    {
        self::assertSame(
            CaptchaProvider::HCaptcha,
            (new HCaptchaWidget())->provider(),
        );
    }

    public function testItEnqueuesTheOfficialHCaptchaScript(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-hcaptcha',
                'https://js.hcaptcha.com/1/api.js',
                [],
                null,
                true,
            );

        (new HCaptchaWidget())->enqueue(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(),
        );

        $this->addToAssertionCount(1);
    }

    public function testItRendersTheCheckboxWidgetWithTheSiteKey(): void
    {
        ob_start();

        (new HCaptchaWidget())->render(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('class="h-captcha"', $output);
        self::assertStringContainsString(
            'data-sitekey="hcaptcha-site-key"',
            $output,
        );
        self::assertStringNotContainsString('<script>', $output);
    }

    public function testItUsesTheHCaptchaResponseTokenField(): void
    {
        self::assertSame(
            'h-captcha-response',
            (new HCaptchaWidget())->tokenFieldName($this->settings()),
        );
    }

    private function settings(): PluginSettings
    {
        return new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            new HCaptchaSettings(
                'hcaptcha-site-key',
                'hcaptcha-secret-key',
                HCaptchaDisplayMode::Checkbox,
            ),
        );
    }
}
