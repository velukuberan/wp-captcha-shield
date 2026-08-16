<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin\Sections;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\WordPress\Admin\Sections\GoogleRecaptchaSettingsSection;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class GoogleRecaptchaSettingsSectionTest extends TestCase
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
        $section = new GoogleRecaptchaSettingsSection(new SettingsFieldRenderer());

        self::assertSame('google', $section->slug());
        self::assertSame('Google reCAPTCHA', $section->label());
        self::assertTrue($section->showsSubmitButton());
    }

    public function testItRendersTheGoogleRecaptchaFields(): void
    {
        $section = new GoogleRecaptchaSettingsSection(new SettingsFieldRenderer());

        $settings = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            new GoogleRecaptchaSettings(
                'google-project-id',
                'google-api-key',
                'google-site-key',
                0.5,
                \WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode::ScoreBased,
            ),
            HCaptchaSettings::defaults(),
        );

        ob_start();
        $section->render($settings);
        $output = ob_get_clean();

        self::assertIsString($output);
        self::assertStringContainsString('google-project-id', $output);
        self::assertStringContainsString('0.5', $output);
    }
}
