<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;

final class EffectiveCaptchaProviderResolverTest extends TestCase
{
    #[DataProvider('defaultResolutionCases')]
    public function testItResolvesTheGlobalSettingWhenFormUsesDefault(
        GlobalCaptchaSetting $globalSetting,
        ?CaptchaProvider $expectedProvider,
    ): void {
        $resolver = new EffectiveCaptchaProviderResolver();

        $result = $resolver->resolve(
            $globalSetting,
            FormCaptchaSetting::useDefault(),
        );

        self::assertSame(
            $expectedProvider,
            $result->provider(),
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{GlobalCaptchaSetting, CaptchaProvider|null}
     * >
     */
    public static function defaultResolutionCases(): iterable
    {
        yield 'disabled global setting' => [
            GlobalCaptchaSetting::disabled(),
            null,
        ];

        foreach (CaptchaProvider::cases() as $provider) {
            yield $provider->value => [
                GlobalCaptchaSetting::provider($provider),
                $provider,
            ];
        }
    }

    #[DataProvider('globalSettings')]
    public function testExplicitFormDisableOverridesAnyGlobalSetting(
        GlobalCaptchaSetting $globalSetting,
    ): void {
        $resolver = new EffectiveCaptchaProviderResolver();

        $result = $resolver->resolve(
            $globalSetting,
            FormCaptchaSetting::disabled(),
        );

        self::assertTrue($result->isDisabled());
        self::assertNull($result->provider());
    }

    /**
     * @return iterable<string, array{GlobalCaptchaSetting}>
     */
    public static function globalSettings(): iterable
    {
        yield 'disabled' => [
            GlobalCaptchaSetting::disabled(),
        ];

        foreach (CaptchaProvider::cases() as $provider) {
            yield $provider->value => [
                GlobalCaptchaSetting::provider($provider),
            ];
        }
    }

    #[DataProvider('explicitProviderCases')]
    public function testExplicitFormProviderOverridesGlobalSetting(
        GlobalCaptchaSetting $globalSetting,
        CaptchaProvider $formProvider,
    ): void {
        $resolver = new EffectiveCaptchaProviderResolver();

        $result = $resolver->resolve(
            $globalSetting,
            FormCaptchaSetting::provider($formProvider),
        );

        self::assertTrue($result->isEnabled());
        self::assertSame(
            $formProvider,
            $result->provider(),
        );
    }

    /**
     * @return iterable<
     *     string,
     *     array{GlobalCaptchaSetting, CaptchaProvider}
     * >
     */
    public static function explicitProviderCases(): iterable
    {
        foreach (CaptchaProvider::cases() as $formProvider) {
            yield "disabled-global-{$formProvider->value}" => [
                GlobalCaptchaSetting::disabled(),
                $formProvider,
            ];
        }

        foreach (CaptchaProvider::cases() as $globalProvider) {
            foreach (CaptchaProvider::cases() as $formProvider) {
                yield "{$globalProvider->value}-{$formProvider->value}" => [
                    GlobalCaptchaSetting::provider($globalProvider),
                    $formProvider,
                ];
            }
        }
    }
}
