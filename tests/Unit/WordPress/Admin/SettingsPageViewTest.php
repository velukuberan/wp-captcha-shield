<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\WordPress\Admin\Sections\SettingsTabSection;
use WpCaptchaShield\WordPress\Admin\SettingsPageView;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class SettingsPageViewTest extends TestCase
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
        Functions\when('esc_url')->returnArg();
        Functions\when('admin_url')->returnArg();
        Functions\when('wp_nonce_field')->justReturn('');
        Functions\when('submit_button')->justReturn('');
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
    }

    public function testItRendersATabForEachSectionWithTheFirstActive(): void
    {
        $view = new SettingsPageView([
            $this->section('general', 'General', true),
            $this->section('status', 'Status', false),
        ]);

        $output = $this->render($view);

        self::assertStringContainsString('role="tablist"', $output);
        self::assertStringContainsString('General', $output);
        self::assertStringContainsString('Status', $output);
        self::assertStringContainsString('nav-tab-active', $output);
        self::assertStringContainsString('aria-selected="true"', $output);
    }

    public function testItRendersASubmitButtonOnlyForSectionsThatWantOne(): void
    {
        $view = new SettingsPageView([
            $this->section('general', 'General', true, 'general-marker'),
            $this->section('status', 'Status', false, 'status-marker'),
        ]);

        $output = $this->render($view);

        self::assertStringContainsString('general-marker', $output);
        self::assertStringContainsString('status-marker', $output);

        $generalPanelStart = strpos($output, 'wp-captcha-shield-tab-general');
        $statusPanelStart = strpos($output, 'wp-captcha-shield-tab-status');

        self::assertIsInt($generalPanelStart);
        self::assertIsInt($statusPanelStart);
    }

    public function testItShowsAConfigurationWarningForIncompleteProviderSettings(): void
    {
        $view = new SettingsPageView([]);

        $output = $this->render($view);

        self::assertStringContainsString(
            'Cloudflare Turnstile configuration is incomplete.',
            $output,
        );
    }

    private function render(SettingsPageView $view): string
    {
        $settings = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );

        ob_start();
        $view->render($settings);
        $output = ob_get_clean();

        self::assertIsString($output);

        return $output;
    }

    private function section(
        string $slug,
        string $label,
        bool $showsSubmitButton,
        ?string $marker = null,
    ): SettingsTabSection {
        return new class ($slug, $label, $showsSubmitButton, $marker) implements SettingsTabSection {
            public function __construct(
                private readonly string $slug,
                private readonly string $label,
                private readonly bool $showsSubmitButton,
                private readonly ?string $marker,
            ) {
            }

            public function slug(): string
            {
                return $this->slug;
            }

            public function label(): string
            {
                return $this->label;
            }

            public function showsSubmitButton(): bool
            {
                return $this->showsSubmitButton;
            }

            public function render(PluginSettings $settings): void
            {
                unset($settings);

                if ($this->marker !== null) {
                    echo esc_html($this->marker);
                }
            }
        };
    }
}
