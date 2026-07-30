<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\CloudflareTurnstile;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileResponse;

final class CloudflareTurnstileResponseTest extends TestCase
{
    public function testItCreatesASuccessfulResponseWithAnAction(): void
    {
        $response = CloudflareTurnstileResponse::successful(
            ' wordpress_login ',
        );

        self::assertTrue($response->isSuccessful());
        self::assertSame([], $response->errorCodes());
        self::assertSame('wordpress_login', $response->action());
    }

    public function testItCreatesASuccessfulResponseWithoutAnAction(): void
    {
        $response = CloudflareTurnstileResponse::successful();

        self::assertTrue($response->isSuccessful());
        self::assertNull($response->action());
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
        self::assertNull($response->action());
    }

    public function testItRejectsARejectedResponseWithoutErrorCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CloudflareTurnstileResponse::rejected([]);
    }

    public function testItRejectsAnEmptyErrorCode(): void
    {
        $this->expectException(InvalidArgumentException::class);

        CloudflareTurnstileResponse::rejected(['']);
    }
}
