<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration\Provider;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;

final class CloudflareTurnstileModeTest extends TestCase
{
    public function testItDefinesTheSupportedModes(): void
    {
        self::assertSame(
            [
                CloudflareTurnstileMode::Managed,
                CloudflareTurnstileMode::NonInteractive,
                CloudflareTurnstileMode::Invisible,
            ],
            CloudflareTurnstileMode::cases(),
        );
    }

    public function testItDefinesStableStoredValues(): void
    {
        self::assertSame(
            'managed',
            CloudflareTurnstileMode::Managed->value,
        );
        self::assertSame(
            'non_interactive',
            CloudflareTurnstileMode::NonInteractive->value,
        );
        self::assertSame(
            'invisible',
            CloudflareTurnstileMode::Invisible->value,
        );
    }
}
