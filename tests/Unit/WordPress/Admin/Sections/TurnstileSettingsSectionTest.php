<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin\Sections;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Admin\Sections\TurnstileSettingsSection;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class TurnstileSettingsSectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('esc_url')->returnArg();
        Functions\when('wp_kses')->returnArg();
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
        $section = new TurnstileSettingsSection(new SettingsFieldRenderer());

        self::assertSame('turnstile', $section->slug());
        self::assertSame('Cloudflare Turnstile', $section->label());
        self::assertTrue($section->showsSubmitButton());
    }

    public function testItExplainsThatCloudflareControlsTheWidgetMode(): void
    {
        $output = $this->render(CloudflareTurnstileMode::Managed);

        self::assertStringContainsString(
            'The widget mode is configured in your Cloudflare Turnstile dashboard.',
            $output,
        );
        self::assertStringContainsString(
            'Changing this setting does not change the widget mode in Cloudflare.',
            $output,
        );
    }

    public function testItShowsThePrivacyWarningForInvisibleMode(): void
    {
        $output = $this->render(CloudflareTurnstileMode::Invisible);

        self::assertStringContainsString(
            'Cloudflare requires websites using Invisible Turnstile',
            $output,
        );
        self::assertStringContainsString(
            'Turnstile Privacy Addendum',
            $output,
        );
        self::assertStringContainsString(
            'https://www.cloudflare.com/turnstile-privacy-policy/',
            $output,
        );
        self::assertStringContainsString(
            'rel="noopener noreferrer"',
            $output,
        );
    }

    #[DataProvider('visibleModes')]
    public function testItDoesNotShowThePrivacyWarningForVisibleModes(
        CloudflareTurnstileMode $mode,
    ): void {
        $output = $this->render($mode);

        self::assertStringNotContainsString(
            'Cloudflare requires websites using Invisible Turnstile',
            $output,
        );
    }

    /**
     * @return iterable<string, array{CloudflareTurnstileMode}>
     */
    public static function visibleModes(): iterable
    {
        yield 'managed' => [CloudflareTurnstileMode::Managed];
        yield 'non-interactive' => [
            CloudflareTurnstileMode::NonInteractive,
        ];
    }

    private function render(CloudflareTurnstileMode $mode): string
    {
        $section = new TurnstileSettingsSection(new SettingsFieldRenderer());

        $settings = new PluginSettings(
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

        ob_start();
        $section->render($settings);
        $output = ob_get_clean();

        self::assertIsString($output);

        return $output;
    }
}
