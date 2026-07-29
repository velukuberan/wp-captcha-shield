<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration\Provider;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;

final class HCaptchaDisplayModeTest extends TestCase
{
    public function testItDefinesTheSupportedModes(): void
    {
        self::assertSame(
            [
                HCaptchaDisplayMode::Checkbox,
                HCaptchaDisplayMode::Invisible,
            ],
            HCaptchaDisplayMode::cases(),
        );
    }

    public function testItDefinesStableStoredValues(): void
    {
        self::assertSame(
            'checkbox',
            HCaptchaDisplayMode::Checkbox->value,
        );
        self::assertSame(
            'invisible',
            HCaptchaDisplayMode::Invisible->value,
        );
    }
}
