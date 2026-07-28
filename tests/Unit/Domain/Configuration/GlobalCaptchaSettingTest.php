<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;

final class GlobalCaptchaSettingTest extends TestCase
{
    public function testItCanBeDisabled(): void
    {
        $setting = GlobalCaptchaSetting::disabled();

        self::assertTrue($setting->isDisabled());
        self::assertFalse($setting->isEnabled());
        self::assertNull($setting->selectedProvider());
    }

    public function testItCanContainAProvider(): void
    {
        $setting = GlobalCaptchaSetting::provider(
            CaptchaProvider::CloudflareTurnstile,
        );

        self::assertFalse($setting->isDisabled());
        self::assertTrue($setting->isEnabled());
        self::assertSame(
            CaptchaProvider::CloudflareTurnstile,
            $setting->selectedProvider(),
        );
    }
}
