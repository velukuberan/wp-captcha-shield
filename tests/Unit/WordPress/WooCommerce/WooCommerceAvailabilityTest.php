<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceAvailability;

final class WooCommerceAvailabilityTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItReportsWooCommerceAsUnavailableWhenClassIsAbsent(): void
    {
        self::assertFalse(
            (new WooCommerceAvailability())->isAvailable(),
        );
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItReportsWooCommerceAsAvailableWhenClassExists(): void
    {
        eval('class WooCommerce {}');

        self::assertTrue(
            (new WooCommerceAvailability())->isAvailable(),
        );
    }
}
