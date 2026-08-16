<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin\Sections;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;
use WpCaptchaShield\WordPress\Admin\Sections\HCaptchaSettingsSection;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class HCaptchaSettingsSectionTest extends TestCase
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
        $section = new HCaptchaSettingsSection(new SettingsFieldRenderer());

        self::assertSame('hcaptcha', $section->slug());
        self::assertSame('hCaptcha', $section->label());
        self::assertTrue($section->showsSubmitButton());
    }

    public function testItRendersTheHCaptchaFields(): void
    {
        $section = new HCaptchaSettingsSection(new SettingsFieldRenderer());

        $settings = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            new HCaptchaSettings(
                'hcaptcha-site-key',
                'hcaptcha-secret-key',
                HCaptchaDisplayMode::Invisible,
            ),
        );

        ob_start();
        $section->render($settings);
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('hcaptcha-site-key', $output);
        self::assertStringContainsString(
            'A value is stored. Leave blank to keep it unchanged.',
            $output,
        );
    }
}
