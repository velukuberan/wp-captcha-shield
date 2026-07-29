<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Http;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Http\HttpResponse;

final class HttpResponseTest extends TestCase
{
    public function testItExposesTheStatusCodeAndBody(): void
    {
        $response = new HttpResponse(
            200,
            '{"success":true}',
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame(
            '{"success":true}',
            $response->body(),
        );
    }

    #[DataProvider('invalidStatusCodeCases')]
    public function testItRejectsAnInvalidStatusCode(
        int $statusCode,
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The HTTP status code must be between 100 and 599.',
        );

        new HttpResponse($statusCode, '');
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function invalidStatusCodeCases(): iterable
    {
        yield 'below minimum' => [99];
        yield 'above maximum' => [600];
    }
}
