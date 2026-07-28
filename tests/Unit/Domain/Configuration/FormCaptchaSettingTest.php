<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration;

use LogicException;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;

final class FormCaptchaSettingTest extends TestCase
{
    public function testItCanUseTheGlobalDefault(): void
    {
        $setting = FormCaptchaSetting::useDefault();

        self::assertTrue($setting->usesDefault());
        self::assertFalse($setting->isDisabled());
        self::assertFalse($setting->usesProvider());
    }

    public function testItCanDisableCaptcha(): void
    {
        $setting = FormCaptchaSetting::disabled();

        self::assertFalse($setting->usesDefault());
        self::assertTrue($setting->isDisabled());
        self::assertFalse($setting->usesProvider());
    }

    public function testItCanContainAProvider(): void
    {
        $setting = FormCaptchaSetting::provider(
            CaptchaProvider::HCaptcha,
        );

        self::assertFalse($setting->usesDefault());
        self::assertFalse($setting->isDisabled());
        self::assertTrue($setting->usesProvider());
        self::assertSame(
            CaptchaProvider::HCaptcha,
            $setting->selectedProvider(),
        );
    }

    public function testProviderCannotBeReadFromDefaultSetting(): void
    {
        $setting = FormCaptchaSetting::useDefault();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The form setting does not contain a CAPTCHA provider.',
        );

        $setting->selectedProvider();
    }

    public function testProviderCannotBeReadFromDisabledSetting(): void
    {
        $setting = FormCaptchaSetting::disabled();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'The form setting does not contain a CAPTCHA provider.',
        );

        $setting->selectedProvider();
    }
}
