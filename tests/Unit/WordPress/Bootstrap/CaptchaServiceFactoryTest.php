<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Bootstrap;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Tests\Support\Http\NonCallingHttpClient;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CloudflareTurnstileConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\GoogleRecaptchaConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\HCaptchaConfiguration;

final class CaptchaServiceFactoryTest extends TestCase
{
    #[DataProvider('configuredProviderCases')]
    public function testItRegistersConfiguredProviders(
        CaptchaProviderConfiguration $configuration,
        CaptchaProvider $provider,
    ): void {
        $service = (new CaptchaServiceFactory(
            new NonCallingHttpClient(),
        ))->create($configuration);

        $result = $service->verify(
            EffectiveCaptchaProvider::enabled($provider),
            new CaptchaVerificationRequest(''),
        );

        self::assertSame(VerificationStatus::Failed, $result->status());
        self::assertSame(
            VerificationFailureReason::MissingToken,
            $result->reason(),
        );
    }

    #[DataProvider('unconfiguredProviderCases')]
    public function testItDoesNotRegisterUnconfiguredProviders(
        CaptchaProvider $provider,
    ): void {
        $service = (new CaptchaServiceFactory(
            new NonCallingHttpClient(),
        ))->create(self::emptyConfiguration());

        $result = $service->verify(
            EffectiveCaptchaProvider::enabled($provider),
            new CaptchaVerificationRequest('token'),
        );

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MissingConfiguration,
            $result->reason(),
        );
    }

    public function testDisabledProviderStillSucceedsWithNoRegistrations(): void
    {
        $service = (new CaptchaServiceFactory(
            new NonCallingHttpClient(),
        ))->create(self::emptyConfiguration());

        $result = $service->verify(
            EffectiveCaptchaProvider::disabled(),
            new CaptchaVerificationRequest(''),
        );

        self::assertSame(
            VerificationStatus::Successful,
            $result->status(),
        );
    }

    /**
     * @return iterable<string, array{CaptchaProviderConfiguration, CaptchaProvider}>
     */
    public static function configuredProviderCases(): iterable
    {
        yield 'Turnstile' => [
            new CaptchaProviderConfiguration(
                new CloudflareTurnstileConfiguration('secret'),
                self::emptyGoogleConfiguration(),
                new HCaptchaConfiguration(''),
            ),
            CaptchaProvider::CloudflareTurnstile,
        ];

        yield 'Google reCAPTCHA' => [
            new CaptchaProviderConfiguration(
                new CloudflareTurnstileConfiguration(''),
                new GoogleRecaptchaConfiguration(
                    'project',
                    'api-key',
                    'site-key',
                    0.5,
                ),
                new HCaptchaConfiguration(''),
            ),
            CaptchaProvider::GoogleRecaptcha,
        ];

        yield 'hCaptcha' => [
            new CaptchaProviderConfiguration(
                new CloudflareTurnstileConfiguration(''),
                self::emptyGoogleConfiguration(),
                new HCaptchaConfiguration('secret'),
            ),
            CaptchaProvider::HCaptcha,
        ];
    }

    /**
     * @return iterable<string, array{CaptchaProvider}>
     */
    public static function unconfiguredProviderCases(): iterable
    {
        yield 'Turnstile' => [CaptchaProvider::CloudflareTurnstile];
        yield 'Google reCAPTCHA' => [CaptchaProvider::GoogleRecaptcha];
        yield 'hCaptcha' => [CaptchaProvider::HCaptcha];
    }

    private static function emptyConfiguration(): CaptchaProviderConfiguration
    {
        return new CaptchaProviderConfiguration(
            new CloudflareTurnstileConfiguration(''),
            self::emptyGoogleConfiguration(),
            new HCaptchaConfiguration(''),
        );
    }

    private static function emptyGoogleConfiguration(): GoogleRecaptchaConfiguration
    {
        return new GoogleRecaptchaConfiguration('', '', '', 0.5);
    }
}
