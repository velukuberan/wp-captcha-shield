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
        Functions\when('sanitize_html_class')->returnArg();
    }

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!defined('WP_CAPTCHA_SHIELD_URL')) {
            define(
                'WP_CAPTCHA_SHIELD_URL',
                'https://example.com/wp-content/plugins/wp-captcha-shield/',
            );
        }

        if (!defined('WP_CAPTCHA_SHIELD_VERSION')) {
            define('WP_CAPTCHA_SHIELD_VERSION', '1.0.0');
        }
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

    public function testItEnqueuesTheCheckboxScript(): void
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
            $this->settings(HCaptchaDisplayMode::Checkbox),
        );

        $this->addToAssertionCount(1);
    }

    public function testItEnqueuesInvisibleScripts(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-hcaptcha-invisible',
                WP_CAPTCHA_SHIELD_URL . 'assets/js/hcaptcha-invisible.js',
                [],
                WP_CAPTCHA_SHIELD_VERSION,
                true,
            );

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-hcaptcha',
                'https://js.hcaptcha.com/1/api.js?'
                . 'onload=wpCaptchaShieldHCaptchaOnload&render=explicit',
                ['wp-captcha-shield-hcaptcha-invisible'],
                null,
                true,
            );

        (new HCaptchaWidget())->enqueue(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(HCaptchaDisplayMode::Invisible),
        );

        $this->addToAssertionCount(2);
    }

    public function testItRendersTheCheckboxWidgetWithTheSiteKey(): void
    {
        $output = $this->render(HCaptchaDisplayMode::Checkbox);

        self::assertStringContainsString('class="h-captcha"', $output);
        self::assertStringContainsString(
            'data-sitekey="hcaptcha-site-key"',
            $output,
        );
        self::assertStringNotContainsString(
            'wp-captcha-shield-hcaptcha-invisible-widget',
            $output,
        );
        self::assertStringNotContainsString('<script>', $output);
    }

    public function testItRendersInvisibleConfiguration(): void
    {
        $output = $this->render(HCaptchaDisplayMode::Invisible);

        self::assertStringContainsString(
            'id="wp-captcha-shield-hcaptcha-invisible-widget-custom-form"',
            $output,
        );
        self::assertStringContainsString(
            'class="wp-captcha-shield-hcaptcha-invisible-widget"',
            $output,
        );
        self::assertStringContainsString(
            'data-form-id="custom-form"',
            $output,
        );
        self::assertStringContainsString(
            'data-site-key="hcaptcha-site-key"',
            $output,
        );
        self::assertStringNotContainsString('class="h-captcha"', $output);
        self::assertStringNotContainsString('<script>', $output);
    }

    public function testItUsesTheHCaptchaResponseTokenField(): void
    {
        $widget = new HCaptchaWidget();

        self::assertSame(
            'h-captcha-response',
            $widget->tokenFieldName(
                $this->settings(HCaptchaDisplayMode::Checkbox),
            ),
        );
        self::assertSame(
            'h-captcha-response',
            $widget->tokenFieldName(
                $this->settings(HCaptchaDisplayMode::Invisible),
            ),
        );
    }

    private function render(HCaptchaDisplayMode $mode): string
    {
        ob_start();

        (new HCaptchaWidget())->render(
            new CaptchaWidgetContext('custom_action', 'custom-form'),
            $this->settings($mode),
        );

        $output = ob_get_clean();
        self::assertIsString($output);

        return $output;
    }

    private function settings(HCaptchaDisplayMode $mode): PluginSettings
    {
        return new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            new HCaptchaSettings(
                'hcaptcha-site-key',
                'hcaptcha-secret-key',
                $mode,
            ),
        );
    }
}
