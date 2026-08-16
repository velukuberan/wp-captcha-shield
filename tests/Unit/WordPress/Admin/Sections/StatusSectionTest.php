<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin\Sections;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Environment\EnvironmentCompatibility;
use WpCaptchaShield\WordPress\Admin\Sections\StatusSection;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class StatusSectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
        Functions\when('esc_html__')->returnArg();
        Functions\when('esc_html')->returnArg();
        Functions\when('esc_attr')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
    }

    public function testSlugLabelAndSubmitButton(): void
    {
        $section = new StatusSection(new EnvironmentCompatibility());

        self::assertSame('status', $section->slug());
        self::assertSame('Status', $section->label());
        self::assertFalse($section->showsSubmitButton());
    }

    public function testItShowsMinimumCurrentAndCompatibility(): void
    {
        Functions\when('get_bloginfo')->alias(
            static fn (string $show): string => $show === 'version' ? '7.0.1' : '',
        );

        $output = $this->render();

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

    public function testItTreatsAShortWordPressVersionAsCompatible(): void
    {
        // WordPress reports "round" releases without a trailing patch
        // component (e.g. "6.7" for the initial 6.7 release, not
        // "6.7.0"). A naive version_compare() against the minimum
        // "6.7.0" would incorrectly flag this as unsupported.
        Functions\when('get_bloginfo')->alias(
            static fn (string $show): string => $show === 'version' ? '6.7' : '',
        );

        $output = $this->render();

        self::assertStringContainsString('6.7', $output);
        self::assertStringNotContainsString('Unsupported', $output);
    }

    private function render(): string
    {
        $section = new StatusSection(new EnvironmentCompatibility());

        $settings = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
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
