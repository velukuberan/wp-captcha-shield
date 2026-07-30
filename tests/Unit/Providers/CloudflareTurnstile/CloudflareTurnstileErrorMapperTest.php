<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\CloudflareTurnstile;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileErrorMapper;

final class CloudflareTurnstileErrorMapperTest extends TestCase
{
    private CloudflareTurnstileErrorMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new CloudflareTurnstileErrorMapper();
    }

    #[DataProvider('errorCodeCases')]
    public function testItMapsCloudflareErrorCodes(
        string $errorCode,
        VerificationStatus $expectedStatus,
        VerificationFailureReason $expectedReason,
    ): void {
        $result = $this->mapper->map([$errorCode]);

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
    public static function errorCodeCases(): iterable
    {
        yield 'missing secret' => [
            'missing-input-secret',
            VerificationStatus::Misconfigured,
            VerificationFailureReason::MissingConfiguration,
        ];

        yield 'invalid secret' => [
            'invalid-input-secret',
            VerificationStatus::Misconfigured,
            VerificationFailureReason::InvalidConfiguration,
        ];

        yield 'internal error' => [
            'internal-error',
            VerificationStatus::Unavailable,
            VerificationFailureReason::NetworkFailure,
        ];

        yield 'missing response' => [
            'missing-input-response',
            VerificationStatus::Failed,
            VerificationFailureReason::MissingToken,
        ];

        yield 'timeout or duplicate' => [
            'timeout-or-duplicate',
            VerificationStatus::Failed,
            VerificationFailureReason::DuplicateToken,
        ];

        yield 'invalid response' => [
            'invalid-input-response',
            VerificationStatus::Failed,
            VerificationFailureReason::InvalidToken,
        ];

        yield 'bad request' => [
            'bad-request',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];

        yield 'unknown error' => [
            'future-cloudflare-error',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];
    }

    public function testConfigurationErrorsHaveHighestPrecedence(): void
    {
        $result = $this->mapper->map([
            'invalid-input-response',
            'internal-error',
            'missing-input-secret',
        ]);

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MissingConfiguration,
            $result->reason(),
        );
    }

    public function testInternalErrorsPrecedeTokenErrors(): void
    {
        $result = $this->mapper->map([
            'invalid-input-response',
            'internal-error',
        ]);

        self::assertSame(
            VerificationStatus::Unavailable,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::NetworkFailure,
            $result->reason(),
        );
    }
}
