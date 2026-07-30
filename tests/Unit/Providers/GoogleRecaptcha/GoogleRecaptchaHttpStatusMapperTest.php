<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaHttpStatusMapper;

final class GoogleRecaptchaHttpStatusMapperTest extends TestCase
{
    #[DataProvider('successfulStatusCases')]
    public function testItReturnsNullForSuccessfulStatuses(
        int $statusCode,
    ): void {
        self::assertNull(
            GoogleRecaptchaHttpStatusMapper::map($statusCode),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function successfulStatusCases(): iterable
    {
        yield 'minimum successful status' => [200];
        yield 'created' => [201];
        yield 'no content' => [204];
        yield 'maximum successful status' => [299];
    }

    #[DataProvider('configurationFailureStatusCases')]
    public function testItMapsConfigurationFailures(
        int $statusCode,
    ): void {
        $result = GoogleRecaptchaHttpStatusMapper::map(
            $statusCode,
        );

        self::assertNotNull($result);
        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::InvalidConfiguration,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function configurationFailureStatusCases(): iterable
    {
        yield 'bad request' => [400];
        yield 'unauthorized' => [401];
        yield 'forbidden' => [403];
        yield 'not found' => [404];
    }

    #[DataProvider('networkFailureStatusCases')]
    public function testItMapsOtherStatusesToNetworkFailure(
        int $statusCode,
    ): void {
        $result = GoogleRecaptchaHttpStatusMapper::map(
            $statusCode,
        );

        self::assertNotNull($result);
        self::assertSame(
            VerificationStatus::Unavailable,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::NetworkFailure,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function networkFailureStatusCases(): iterable
    {
        yield 'informational status' => [100];
        yield 'redirect status' => [302];
        yield 'request timeout' => [408];
        yield 'conflict' => [409];
        yield 'unprocessable content' => [422];
        yield 'rate limited' => [429];
        yield 'server error' => [500];
        yield 'service unavailable' => [503];
    }
}
