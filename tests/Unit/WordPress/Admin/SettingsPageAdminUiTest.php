<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class SettingsPageAdminUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_attr__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('selected')->justReturn('');
        Functions\when('get_bloginfo')->alias(static function (string $show): string {
            return $show === 'version' ? '7.0.1' : '';
        });
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
    }

    public function testItRendersTheFiveAdminTabs(): void
    {
        $output = $this->renderPrivateMethod('renderTabs');

        self::assertStringContainsString('General', $output);
        self::assertStringContainsString('Cloudflare Turnstile', $output);
        self::assertStringContainsString('Google reCAPTCHA', $output);
        self::assertStringContainsString('hCaptcha', $output);
        self::assertStringContainsString('Status', $output);
        self::assertStringContainsString('role="tablist"', $output);
        self::assertStringContainsString('aria-selected="true"', $output);
    }

    public function testGeneralSettingsGroupWordPressAndWooCommerceForms(): void
    {
        $output = $this->renderPrivateMethod(
            'renderGeneralSection',
            $this->settings(),
        );

        self::assertStringContainsString('WordPress forms', $output);
        self::assertStringContainsString('WooCommerce forms', $output);
        self::assertStringContainsString('WordPress login', $output);
        self::assertStringContainsString('WooCommerce checkout', $output);
    }

    public function testItRendersAccessibleFieldHelp(): void
    {
        $output = $this->renderPrivateMethod(
            'renderGeneralSection',
            $this->settings(),
        );

        self::assertStringContainsString(
            'aria-label="Help for Default provider"',
            $output,
        );
        self::assertStringContainsString('role="tooltip"', $output);
        self::assertStringContainsString(
            'Used by forms configured to “Use default”',
            $output,
        );
    }

    public function testStatusTabShowsMinimumCurrentAndCompatibility(): void
    {
        $output = $this->renderPrivateMethod('renderStatusSection');

        self::assertStringContainsString('Minimum supported', $output);
        self::assertStringContainsString('Current', $output);
        self::assertStringContainsString('8.1.0', $output);
        self::assertStringContainsString(PHP_VERSION, $output);
        self::assertStringContainsString('6.7.0', $output);
        self::assertStringContainsString('7.0.1', $output);
        self::assertStringContainsString('10.1.0', $output);
        self::assertStringContainsString('Not active', $output);
        self::assertStringContainsString('Compatible', $output);
        self::assertStringContainsString('Optional', $output);
        self::assertStringContainsString(
            'WooCommerce is optional. WordPress form protection remains available when WooCommerce is not active.',
            $output,
        );
    }

    public function testItLoadsAssetsOnlyOnItsSettingsPage(): void
    {
        $page = $this->page();

        Functions\expect('wp_enqueue_style')->never();
        Functions\expect('wp_enqueue_script')->never();

        $page->enqueueAssets('settings_page_other-plugin');
        $this->addToAssertionCount(1);
    }

    public function testItLoadsAdminAssetsOnItsSettingsPage(): void
    {
        if (!defined('WP_CAPTCHA_SHIELD_URL')) {
            define('WP_CAPTCHA_SHIELD_URL', 'https://example.test/plugin/');
        }

        if (!defined('WP_CAPTCHA_SHIELD_VERSION')) {
            define('WP_CAPTCHA_SHIELD_VERSION', 'test');
        }

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with(
                'wp-captcha-shield-admin-settings',
                WP_CAPTCHA_SHIELD_URL . 'assets/css/admin-settings.css',
                [],
                WP_CAPTCHA_SHIELD_VERSION,
            );

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-admin-settings',
                WP_CAPTCHA_SHIELD_URL . 'assets/js/admin-settings.js',
                [],
                WP_CAPTCHA_SHIELD_VERSION,
                true,
            );

        $this->page()->enqueueAssets('settings_page_wp-captcha-shield');
        $this->addToAssertionCount(1);
    }

    private function page(): SettingsPage
    {
        $repository = Mockery::mock(SettingsRepository::class);

        return new SettingsPage(
            $repository,
            new SettingsInputMapper(),
            (new SupportedForms())->labels(),
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

    private function renderPrivateMethod(
        string $methodName,
        mixed ...$arguments,
    ): string {
        $method = new ReflectionMethod(SettingsPage::class, $methodName);
        $method->setAccessible(true);

        ob_start();
        $method->invoke($this->page(), ...$arguments);
        $output = ob_get_clean();

        self::assertIsString($output);

        return $output;
    }
}
