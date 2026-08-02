<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap\Configuration;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CloudflareTurnstileConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\GoogleRecaptchaConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\HCaptchaConfiguration;

final class CaptchaProviderConfigurationTest extends TestCase
{
    public function testItExposesProviderConfigurations(): void
    {
        $turnstile = new CloudflareTurnstileConfiguration('turnstile');
        $google = new GoogleRecaptchaConfiguration(
            'project',
            'api-key',
            'site-key',
            0.5,
            GoogleRecaptchaMode::ScoreBased,
        );
        $hCaptcha = new HCaptchaConfiguration('hcaptcha', 'site-key');

        $configuration = new CaptchaProviderConfiguration(
            $turnstile,
            $google,
            $hCaptcha,
        );

        self::assertSame($turnstile, $configuration->turnstile());
        self::assertSame($google, $configuration->googleRecaptcha());
        self::assertSame($hCaptcha, $configuration->hCaptcha());
    }
}
