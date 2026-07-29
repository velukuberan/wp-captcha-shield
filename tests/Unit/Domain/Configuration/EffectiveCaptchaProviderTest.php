<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;

final class EffectiveCaptchaProviderTest extends TestCase
{
    public function testItCanRepresentDisabledCaptcha(): void
    {
        $effectiveProvider = EffectiveCaptchaProvider::disabled();

        self::assertTrue($effectiveProvider->isDisabled());
        self::assertFalse($effectiveProvider->isEnabled());
        self::assertNull($effectiveProvider->provider());
    }

    public function testItCanRepresentAnEnabledProvider(): void
    {
        $effectiveProvider = EffectiveCaptchaProvider::enabled(
            CaptchaProvider::GoogleRecaptcha
        );

        self::assertFalse($effectiveProvider->isDisabled());
        self::assertTrue($effectiveProvider->isEnabled());
        self::assertSame(
            CaptchaProvider::GoogleRecaptcha,
            $effectiveProvider->provider(),
        );
    }
}
