<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\GoogleRecaptchaWidget;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class GoogleRecaptchaWidgetTest extends TestCase
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

    public function testItReportsGoogleRecaptchaAsItsProvider(): void
    {
        self::assertSame(
            CaptchaProvider::GoogleRecaptcha,
            (new GoogleRecaptchaWidget())->provider(),
        );
    }

    public function testItEnqueuesScoreBasedScripts(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-google-recaptcha',
                'https://www.google.com/recaptcha/enterprise.js?render='
                . 'google-site-key',
                [],
                null,
                true,
            );

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-google-recaptcha-score-based',
                WP_CAPTCHA_SHIELD_URL
                . 'assets/js/google-recaptcha-score-based.js',
                ['wp-captcha-shield-google-recaptcha'],
                WP_CAPTCHA_SHIELD_VERSION,
                true,
            );

        (new GoogleRecaptchaWidget())->enqueue(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(GoogleRecaptchaMode::ScoreBased),
        );

        $this->addToAssertionCount(2);
    }

    public function testItEnqueuesTheCheckboxScript(): void
    {
        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-google-recaptcha',
                'https://www.google.com/recaptcha/enterprise.js',
                [],
                null,
                true,
            );

        (new GoogleRecaptchaWidget())->enqueue(
            new CaptchaWidgetContext('wordpress_login', 'loginform'),
            $this->settings(GoogleRecaptchaMode::Checkbox),
        );

        $this->addToAssertionCount(1);
    }

    public function testItRendersCheckboxConfiguration(): void
    {
        ob_start();

        (new GoogleRecaptchaWidget())->render(
            new CaptchaWidgetContext('custom_action', 'custom-form'),
            $this->settings(GoogleRecaptchaMode::Checkbox),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString(
            'class="g-recaptcha"',
            $output,
        );
        self::assertStringContainsString(
            'data-sitekey="google-site-key"',
            $output,
        );
        self::assertStringContainsString(
            'data-action="custom_action"',
            $output,
        );
        self::assertStringNotContainsString(
            'name="wp_captcha_shield_google_token"',
            $output,
        );
        self::assertStringNotContainsString('<script>', $output);
    }

    public function testItUsesTheGoogleTokenFieldForCheckboxMode(): void
    {
        self::assertSame(
            'g-recaptcha-response',
            (new GoogleRecaptchaWidget())->tokenFieldName(
                $this->settings(GoogleRecaptchaMode::Checkbox),
            ),
        );
    }

    public function testItRendersScoreBasedConfiguration(): void
    {
        ob_start();

        (new GoogleRecaptchaWidget())->render(
            new CaptchaWidgetContext('custom_action', 'custom-form'),
            $this->settings(GoogleRecaptchaMode::ScoreBased),
        );

        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString(
            'name="wp_captcha_shield_google_token"',
            $output,
        );
        self::assertStringContainsString(
            'id="wp_captcha_shield_google_token-custom-form"',
            $output,
        );
        self::assertStringContainsString(
            'class="wp-captcha-shield-google-score-token"',
            $output,
        );
        self::assertStringContainsString(
            'data-form-id="custom-form"',
            $output,
        );
        self::assertStringContainsString(
            'data-site-key="google-site-key"',
            $output,
        );
        self::assertStringContainsString(
            'data-action="custom_action"',
            $output,
        );
        self::assertStringNotContainsString('<script>', $output);
        self::assertStringNotContainsString('g-recaptcha', $output);
    }

    public function testItUsesThePluginTokenFieldForScoreBasedMode(): void
    {
        self::assertSame(
            GoogleRecaptchaWidget::TOKEN_FIELD,
            (new GoogleRecaptchaWidget())->tokenFieldName(
                $this->settings(GoogleRecaptchaMode::ScoreBased),
            ),
        );
    }

    private function settings(GoogleRecaptchaMode $mode): PluginSettings
    {
        return new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            new GoogleRecaptchaSettings(
                'google-project-id',
                'google-api-key',
                'google-site-key',
                0.5,
                $mode,
            ),
            HCaptchaSettings::defaults(),
        );
    }
}
