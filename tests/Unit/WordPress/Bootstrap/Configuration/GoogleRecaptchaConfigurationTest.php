<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\GoogleRecaptchaConfiguration;

final class GoogleRecaptchaConfigurationTest extends TestCase
{
    #[DataProvider('configurationCases')]
    public function testItDeterminesWhetherConfigurationIsComplete(
        string $projectId,
        string $apiKey,
        string $siteKey,
        bool $expected,
    ): void {
        $configuration = new GoogleRecaptchaConfiguration(
            $projectId,
            $apiKey,
            $siteKey,
            0.5,
        );

        self::assertSame($expected, $configuration->isConfigured());
        self::assertSame($projectId, $configuration->projectId());
        self::assertSame($apiKey, $configuration->apiKey());
        self::assertSame($siteKey, $configuration->siteKey());
        self::assertSame(0.5, $configuration->minimumScore());
    }

    /**
     * @return iterable<string, array{string, string, string, bool}>
     */
    public static function configurationCases(): iterable
    {
        yield 'all values present' => [
            'project-id',
            'api-key',
            'site-key',
            true,
        ];
        yield 'project ID missing' => ['', 'api-key', 'site-key', false];
        yield 'API key missing' => ['project-id', '', 'site-key', false];
        yield 'site key missing' => ['project-id', 'api-key', '', false];
        yield 'whitespace value' => ['project-id', '   ', 'site-key', false];
    }
}
