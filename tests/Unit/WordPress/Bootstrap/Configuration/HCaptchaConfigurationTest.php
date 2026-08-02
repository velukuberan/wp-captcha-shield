<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\HCaptchaConfiguration;

final class HCaptchaConfigurationTest extends TestCase
{
    #[DataProvider('configurationCases')]
    public function testItDeterminesWhetherConfigurationIsComplete(
        string $secretKey,
        string $siteKey,
        bool $expected,
    ): void {
        $configuration = new HCaptchaConfiguration(
            $secretKey,
            $siteKey,
        );

        self::assertSame($expected, $configuration->isConfigured());
        self::assertSame($secretKey, $configuration->secretKey());
        self::assertSame($siteKey, $configuration->siteKey());
    }

    /**
     * @return iterable<string, array{string, string, bool}>
     */
    public static function configurationCases(): iterable
    {
        yield 'empty secret' => ['', 'site-key', false];
        yield 'whitespace secret' => ['   ', 'site-key', false];
        yield 'empty site key' => ['secret-key', '', false];
        yield 'whitespace site key' => ['secret-key', '   ', false];
        yield 'configured' => ['hcaptcha-secret', 'hcaptcha-site', true];
    }
}
