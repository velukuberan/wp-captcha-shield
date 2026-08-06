<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceAvailability;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceBootstrap;

final class WooCommerceBootstrapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersInitializationAfterPluginsAreLoaded(): void
    {
        $bootstrap = new WooCommerceBootstrap(
            new WooCommerceAvailability(),
        );

        Functions\expect('add_action')
            ->once()
            ->with(
                'plugins_loaded',
                [$bootstrap, 'initialize'],
            );

        $bootstrap->registerHooks();

        $this->addToAssertionCount(1);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItDoesNothingWhenWooCommerceIsUnavailable(): void
    {
        $bootstrap = new WooCommerceBootstrap(
            new WooCommerceAvailability(),
        );

        $bootstrap->initialize();

        self::assertFalse(
            class_exists('WooCommerce'),
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItInitializesSafelyWhenWooCommerceIsAvailable(): void
    {
        eval('class WooCommerce {}');

        $bootstrap = new WooCommerceBootstrap(
            new WooCommerceAvailability(),
        );

        $bootstrap->initialize();

        self::assertTrue(
            class_exists('WooCommerce'),
        );
    }
}
