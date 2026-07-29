<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Verification;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationStatus;

final class VerificationStatusTest extends TestCase
{
    public function testItDefinesTheSupportedStatuses(): void
    {
        self::assertSame(
            [
                VerificationStatus::Successful,
                VerificationStatus::Failed,
                VerificationStatus::Unavailable,
                VerificationStatus::Misconfigured,
            ],
            VerificationStatus::cases(),
        );
    }

    public function testItDefinesStableStoredValues(): void
    {
        self::assertSame(
            'successful',
            VerificationStatus::Successful->value,
        );
        self::assertSame(
            'failed',
            VerificationStatus::Failed->value,
        );
        self::assertSame(
            'unavailable',
            VerificationStatus::Unavailable->value,
        );
        self::assertSame(
            'misconfigured',
            VerificationStatus::Misconfigured->value,
        );
    }
}
