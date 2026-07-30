<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\CloudflareTurnstile;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileResponse;

final class CloudflareTurnstileResponseTest extends TestCase
{
    public function testItCreatesASuccessfulResponse(): void
    {
        $response = CloudflareTurnstileResponse::successful();

        self::assertTrue($response->isSuccessful());
        self::assertSame([], $response->errorCodes());
    }

    public function testItCreatesARejectedResponse(): void
    {
        $response = CloudflareTurnstileResponse::rejected(
            ['invalid-input-response'],
        );

        self::assertFalse($response->isSuccessful());
        self::assertSame(
            ['invalid-input-response'],
            $response->errorCodes(),
        );
    }

    public function testItRejectsARejectedResponseWithoutErrorCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A rejected Turnstile response must contain an error code.',
        );

        CloudflareTurnstileResponse::rejected([]);
    }

    public function testItRejectsAnEmptyErrorCode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A Turnstile error code cannot be empty.',
        );

        CloudflareTurnstileResponse::rejected(['']);
    }
}
