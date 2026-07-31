<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class SettingsPageTurnstileGuidanceTest extends TestCase
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

    public function testItExplainsThatCloudflareControlsTheWidgetMode(): void
    {
        $output = $this->renderTurnstileSection(
            CloudflareTurnstileMode::Managed,
        );

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
        $output = $this->renderTurnstileSection(
            CloudflareTurnstileMode::Invisible,
        );

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
        $output = $this->renderTurnstileSection($mode);

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

    private function renderTurnstileSection(
        CloudflareTurnstileMode $mode,
    ): string {
        $repository = Mockery::mock(SettingsRepository::class);
        $page = new SettingsPage(
            $repository,
            new SettingsInputMapper(),
        );
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

        $method = new ReflectionMethod(
            SettingsPage::class,
            'renderTurnstileSection',
        );
        $method->setAccessible(true);

        ob_start();
        $method->invoke($page, $settings);
        $output = ob_get_clean();

        self::assertIsString($output);

        return $output;
    }
}
