<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Verification;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;

final class CaptchaVerificationRequestTest extends TestCase
{
    public function testItNormalizesVerificationContext(): void
    {
        $request = new CaptchaVerificationRequest(
            ' submitted-token ',
            ' 203.0.113.10 ',
            ' Mozilla/5.0 ',
            ' wordpress_login ',
        );

        self::assertSame('submitted-token', $request->token());
        self::assertSame('203.0.113.10', $request->remoteIp());
        self::assertSame('Mozilla/5.0', $request->userAgent());
        self::assertSame(
            'wordpress_login',
            $request->expectedAction(),
        );
    }

    #[DataProvider('emptyOptionalValueCases')]
    public function testItConvertsEmptyOptionalValuesToNull(
        ?string $value,
    ): void {
        $request = new CaptchaVerificationRequest(
            'token',
            $value,
            $value,
            $value,
        );

        self::assertNull($request->remoteIp());
        self::assertNull($request->userAgent());
        self::assertNull($request->expectedAction());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function emptyOptionalValueCases(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace' => ['   '];
    }
}
