<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Admin\SettingsPageView;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class SettingsPageAdminUiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();

        parent::tearDown();
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
            new SettingsPageView(),
            (new SupportedForms())->labels(),
        );
    }
}
