<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Bootstrap\Plugin;

/**
 * Smoke-tests the composition root: boots the real object graph (nothing
 * mocked except the underlying WordPress hook functions) and asserts
 * every expected hook actually gets registered. Per-form hook argument
 * details are covered by WordPressFormsBootstrapTest and
 * WooCommerceBootstrapTest; this test exists to catch wiring mistakes
 * that only show up when the whole graph is assembled together.
 */
final class PluginTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();

        // SupportedForms::labels(), pulled in via bootSettingsPage(),
        // translates each form label through __().
        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testBootingWiresTheFullObjectGraphAndRegistersEveryHook(): void
    {
        $calls = [];

        Functions\when('add_action')->alias(
            static function (string $hook) use (&$calls): void {
                $calls[] = ['action', $hook];
            },
        );
        Functions\when('add_filter')->alias(
            static function (string $hook) use (&$calls): void {
                $calls[] = ['filter', $hook];
            },
        );

        (new Plugin())->boot();

        self::assertCount(16, $calls);

        // Settings page.
        self::assertContains(['action', 'admin_menu'], $calls);
        self::assertContains(
            ['action', 'admin_post_' . SettingsPage::SAVE_ACTION],
            $calls,
        );
        self::assertContains(['action', 'admin_enqueue_scripts'], $calls);

        // Core WordPress forms.
        self::assertContains(['action', 'login_form'], $calls);
        self::assertContains(['filter', 'authenticate'], $calls);
        self::assertContains(['action', 'register_form'], $calls);
        self::assertContains(['filter', 'registration_errors'], $calls);
        self::assertContains(['action', 'lostpassword_form'], $calls);
        self::assertContains(['action', 'lostpassword_post'], $calls);
        self::assertContains(
            ['filter', 'comment_form_submit_field'],
            $calls,
        );
        self::assertContains(['action', 'pre_comment_on_post'], $calls);

        // WooCommerce integration is deferred, not wired eagerly.
        self::assertContains(['action', 'plugins_loaded'], $calls);
        self::assertNotContains(['action', 'woocommerce_login_form'], $calls);
    }
}
