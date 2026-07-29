<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Verification;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;
use WpCaptchaShield\Domain\Verification\VerificationStatus;

final class VerificationResultTest extends TestCase
{
    public function testItCanRepresentSuccessfulVerification(): void
    {
        $result = VerificationResult::successful();

        self::assertSame(
            VerificationStatus::Successful,
            $result->status(),
        );
        self::assertNull($result->reason());
        self::assertTrue($result->isSuccessful());
        self::assertFalse($result->isFailed());
        self::assertFalse($result->isUnavailable());
        self::assertFalse($result->isMisconfigured());
    }

    #[DataProvider('unsuccessfulResultCases')]
    public function testItCanRepresentAnUnsuccessfulVerification(
        VerificationResult $result,
        VerificationStatus $expectedStatus,
        VerificationFailureReason $expectedReason,
    ): void {
        self::assertSame(
            $expectedStatus,
            $result->status(),
        );
        self::assertSame(
            $expectedReason,
            $result->reason(),
        );
        self::assertSame(
            $expectedStatus === VerificationStatus::Successful,
            $result->isSuccessful(),
        );
        self::assertSame(
            $expectedStatus === VerificationStatus::Failed,
            $result->isFailed(),
        );
        self::assertSame(
            $expectedStatus === VerificationStatus::Unavailable,
            $result->isUnavailable(),
        );
        self::assertSame(
            $expectedStatus === VerificationStatus::Misconfigured,
            $result->isMisconfigured(),
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{
     *         VerificationResult,
     *         VerificationStatus,
     *         VerificationFailureReason
     *     }
     * >
     */
    public static function unsuccessfulResultCases(): iterable
    {
        yield 'failed' => [
            VerificationResult::failed(
                VerificationFailureReason::InvalidToken,
            ),
            VerificationStatus::Failed,
            VerificationFailureReason::InvalidToken,
        ];

        yield 'unavailable' => [
            VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            ),
            VerificationStatus::Unavailable,
            VerificationFailureReason::NetworkFailure,
        ];

        yield 'misconfigured' => [
            VerificationResult::misconfigured(
                VerificationFailureReason::MissingConfiguration,
            ),
            VerificationStatus::Misconfigured,
            VerificationFailureReason::MissingConfiguration,
        ];
    }

    #[DataProvider('invalidReasonCases')]
    public function testItRejectsAReasonThatDoesNotMatchTheStatus(
        string $factory,
        VerificationFailureReason $reason,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The verification failure reason does not match '
            . 'the result status.',
        );

        VerificationResult::{$factory}($reason);
    }

    /**
     * @return iterable<
     *     string,
     *     array{string, VerificationFailureReason}
     * >
     */
    public static function invalidReasonCases(): iterable
    {
        yield 'failed with unavailable reason' => [
            'failed',
            VerificationFailureReason::NetworkFailure,
        ];
        yield 'failed with configuration reason' => [
            'failed',
            VerificationFailureReason::MissingConfiguration,
        ];
        yield 'unavailable with failed reason' => [
            'unavailable',
            VerificationFailureReason::InvalidToken,
        ];
        yield 'unavailable with configuration reason' => [
            'unavailable',
            VerificationFailureReason::InvalidConfiguration,
        ];
        yield 'misconfigured with failed reason' => [
            'misconfigured',
            VerificationFailureReason::ProviderRejected,
        ];
        yield 'misconfigured with unavailable reason' => [
            'misconfigured',
            VerificationFailureReason::MalformedResponse,
        ];
    }

    public function testConstructorIsNotPublic(): void
    {
        $constructor = (new ReflectionClass(
            VerificationResult::class,
        ))->getConstructor();

        self::assertNotNull($constructor);
        self::assertTrue($constructor->isPrivate());
    }
}
