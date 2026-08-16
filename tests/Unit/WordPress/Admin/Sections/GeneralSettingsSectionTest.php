<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin\Sections;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\WordPress\Admin\Sections\GeneralSettingsSection;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class GeneralSettingsSectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('selected')->justReturn('');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
    }

    public function testSlugLabelAndSubmitButton(): void
    {
        $section = new GeneralSettingsSection(new SettingsFieldRenderer());

        self::assertSame('general', $section->slug());
        self::assertSame('General', $section->label());
        self::assertTrue($section->showsSubmitButton());
    }

    public function testItGroupsWordPressAndWooCommerceForms(): void
    {
        $section = new GeneralSettingsSection(new SettingsFieldRenderer(), [
            'wp_login' => 'WordPress login',
            'woocommerce_checkout' => 'WooCommerce checkout',
        ]);

        ob_start();
        $section->render($this->settings());
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('WordPress forms', $output);
        self::assertStringContainsString('WooCommerce forms', $output);
        self::assertStringContainsString('WordPress login', $output);
        self::assertStringContainsString('WooCommerce checkout', $output);
    }

    public function testItRendersAccessibleFieldHelp(): void
    {
        $section = new GeneralSettingsSection(new SettingsFieldRenderer());

        ob_start();
        $section->render($this->settings());
        $output = ob_get_clean();

        self::assertIsString($output);
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
