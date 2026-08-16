<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Admin\SettingsPageRegistrar;
use WpCaptchaShield\WordPress\Admin\SettingsPageView;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class SettingsPageRegistrarTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        Mockery::close();
        parent::tearDown();
    }

    public function testItRegistersMenuSaveAndAdminAssetHooks(): void
    {
        $page = new SettingsPage(
            Mockery::mock(SettingsRepository::class),
            new SettingsInputMapper(),
            new SettingsPageView(),
        );
        $registrar = new SettingsPageRegistrar($page);

        Functions\expect('add_action')
            ->once()
            ->with('admin_menu', [$page, 'register']);
        Functions\expect('add_action')
            ->once()
            ->with(
                'admin_post_' . SettingsPage::SAVE_ACTION,
                [$page, 'save'],
            );
        Functions\expect('add_action')
            ->once()
            ->with('admin_enqueue_scripts', [$page, 'enqueueAssets']);

        $registrar->registerHooks();
        $this->addToAssertionCount(1);
    }
}
