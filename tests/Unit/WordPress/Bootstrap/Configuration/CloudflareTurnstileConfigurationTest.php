<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CloudflareTurnstileConfiguration;

final class CloudflareTurnstileConfigurationTest extends TestCase
{
    #[DataProvider('configurationCases')]
    public function testItDeterminesWhetherConfigurationIsComplete(
        string $secretKey,
        bool $expected,
    ): void {
        $configuration = new CloudflareTurnstileConfiguration($secretKey);

        self::assertSame($expected, $configuration->isConfigured());
        self::assertSame($secretKey, $configuration->secretKey());
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function configurationCases(): iterable
    {
        yield 'empty secret' => ['', false];
        yield 'whitespace secret' => ['   ', false];
        yield 'configured secret' => ['turnstile-secret', true];
    }
}
