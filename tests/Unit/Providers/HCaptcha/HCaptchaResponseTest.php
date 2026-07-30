<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\HCaptcha;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaResponse;

final class HCaptchaResponseTest extends TestCase
{
    public function testItCreatesASuccessfulResponse(): void
    {
        $response = HCaptchaResponse::successful();

        self::assertTrue($response->isSuccessful());
        self::assertSame([], $response->errorCodes());
    }

    public function testItCreatesARejectedResponse(): void
    {
        $response = HCaptchaResponse::rejected([
            'invalid-input-response',
        ]);

        self::assertFalse($response->isSuccessful());
        self::assertSame(
            ['invalid-input-response'],
            $response->errorCodes(),
        );
    }

    public function testItRejectsARejectedResponseWithoutErrors(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HCaptchaResponse::rejected([]);
    }

    public function testItRejectsAnEmptyErrorCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        HCaptchaResponse::rejected(['']);
    }
}
