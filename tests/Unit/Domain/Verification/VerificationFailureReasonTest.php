<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Verification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;

final class VerificationFailureReasonTest extends TestCase
{
    #[DataProvider('reasonStatusCases')]
    public function testItMapsEachReasonToItsStatus(
        VerificationFailureReason $reason,
        VerificationStatus $expectedStatus,
    ): void {
        self::assertSame(
            $expectedStatus,
            VerificationFailureReason::statusFor($reason),
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{VerificationFailureReason, VerificationStatus}
     * >
     */
    public static function reasonStatusCases(): iterable
    {
        yield 'missing token' => [
            VerificationFailureReason::MissingToken,
            VerificationStatus::Failed,
        ];
        yield 'invalid token' => [
            VerificationFailureReason::InvalidToken,
            VerificationStatus::Failed,
        ];
        yield 'expired token' => [
            VerificationFailureReason::ExpiredToken,
            VerificationStatus::Failed,
        ];
        yield 'duplicate token' => [
            VerificationFailureReason::DuplicateToken,
            VerificationStatus::Failed,
        ];
        yield 'low score' => [
            VerificationFailureReason::LowScore,
            VerificationStatus::Failed,
        ];
        yield 'provider rejected' => [
            VerificationFailureReason::ProviderRejected,
            VerificationStatus::Failed,
        ];
        yield 'network failure' => [
            VerificationFailureReason::NetworkFailure,
            VerificationStatus::Unavailable,
        ];
        yield 'malformed response' => [
            VerificationFailureReason::MalformedResponse,
            VerificationStatus::Unavailable,
        ];
        yield 'missing configuration' => [
            VerificationFailureReason::MissingConfiguration,
            VerificationStatus::Misconfigured,
        ];
        yield 'invalid configuration' => [
            VerificationFailureReason::InvalidConfiguration,
            VerificationStatus::Misconfigured,
        ];
    }

    public function testItDefinesStableStoredValues(): void
    {
        self::assertSame(
            [
                'missing_token',
                'invalid_token',
                'expired_token',
                'duplicate_token',
                'low_score',
                'provider_rejected',
                'network_failure',
                'malformed_response',
                'missing_configuration',
                'invalid_configuration',
            ],
            array_map(
                static fn (
                    VerificationFailureReason $reason,
                ): string => $reason->value,
                VerificationFailureReason::cases(),
            ),
        );
    }
}
