<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaErrorMapper;

final class GoogleRecaptchaErrorMapperTest extends TestCase
{
    #[DataProvider('invalidReasonCases')]
    public function testItMapsGoogleInvalidReasons(
        string $invalidReason,
        VerificationStatus $expectedStatus,
        VerificationFailureReason $expectedReason,
    ): void {
        $result = (new GoogleRecaptchaErrorMapper())->map(
            $invalidReason,
        );

        self::assertSame($expectedStatus, $result->status());
        self::assertSame($expectedReason, $result->reason());
    }

    /**
     * @return iterable<
     *     string,
     *     array{
     *         string,
     *         VerificationStatus,
     *         VerificationFailureReason
     *     }
     * >
     */
    public static function invalidReasonCases(): iterable
    {
        yield 'missing token' => [
            'MISSING',
            VerificationStatus::Failed,
            VerificationFailureReason::MissingToken,
        ];

        yield 'malformed token' => [
            'MALFORMED',
            VerificationStatus::Failed,
            VerificationFailureReason::InvalidToken,
        ];

        yield 'expired token' => [
            'EXPIRED',
            VerificationStatus::Failed,
            VerificationFailureReason::ExpiredToken,
        ];

        yield 'duplicate token' => [
            'DUPE',
            VerificationStatus::Failed,
            VerificationFailureReason::DuplicateToken,
        ];

        yield 'key mismatch' => [
            'KEY_MISMATCH',
            VerificationStatus::Misconfigured,
            VerificationFailureReason::InvalidConfiguration,
        ];

        yield 'domain mismatch' => [
            'DOMAIN_MISMATCH',
            VerificationStatus::Misconfigured,
            VerificationFailureReason::InvalidConfiguration,
        ];

        yield 'unexpected action' => [
            'UNEXPECTED_ACTION',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];

        yield 'browser error' => [
            'BROWSER_ERROR',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];

        yield 'future reason' => [
            'FUTURE_REASON',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];
    }
}
