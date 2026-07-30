<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\HCaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaErrorMapper;

final class HCaptchaErrorMapperTest extends TestCase
{
    private HCaptchaErrorMapper $mapper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = new HCaptchaErrorMapper();
    }

    #[DataProvider('errorCodeCases')]
    public function testItMapsHCaptchaErrorCodes(
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

        yield 'missing response' => [
            'missing-input-response',
            VerificationStatus::Failed,
            VerificationFailureReason::MissingToken,
        ];

        yield 'expired response' => [
            'expired-input-response',
            VerificationStatus::Failed,
            VerificationFailureReason::DuplicateToken,
        ];

        yield 'invalid response' => [
            'invalid-input-response',
            VerificationStatus::Failed,
            VerificationFailureReason::InvalidToken,
        ];

        yield 'unknown error' => [
            'future-hcaptcha-error',
            VerificationStatus::Failed,
            VerificationFailureReason::ProviderRejected,
        ];
    }

    public function testConfigurationErrorsHaveHighestPrecedence(): void
    {
        $result = $this->mapper->map([
            'invalid-input-response',
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
}
