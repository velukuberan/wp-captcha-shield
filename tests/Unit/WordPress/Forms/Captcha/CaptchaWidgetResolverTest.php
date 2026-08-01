<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaWidgetResolverTest extends TestCase
{
    public function testItResolvesTheWidgetForAProvider(): void
    {
        $widget = $this->widget(CaptchaProvider::CloudflareTurnstile);
        $resolver = new CaptchaWidgetResolver([$widget]);

        self::assertSame(
            $widget,
            $resolver->resolve(CaptchaProvider::CloudflareTurnstile),
        );
    }

    public function testItRejectsDuplicateProviderRegistrations(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new CaptchaWidgetResolver([
            $this->widget(CaptchaProvider::HCaptcha),
            $this->widget(CaptchaProvider::HCaptcha),
        ]);
    }

    public function testItRejectsAnUnregisteredProvider(): void
    {
        $resolver = new CaptchaWidgetResolver([]);

        $this->expectException(RuntimeException::class);

        $resolver->resolve(CaptchaProvider::GoogleRecaptcha);
    }

    private function widget(CaptchaProvider $provider): CaptchaWidget
    {
        return new class ($provider) implements CaptchaWidget {
            public function __construct(
                private readonly CaptchaProvider $captchaProvider,
            ) {
            }

            public function provider(): CaptchaProvider
            {
                return $this->captchaProvider;
            }

            public function enqueue(
                CaptchaWidgetContext $context,
                PluginSettings $settings,
            ): void {
                unset($context, $settings);
            }

            public function render(
                CaptchaWidgetContext $context,
                PluginSettings $settings,
            ): void {
                unset($context, $settings);
            }

            public function tokenFieldName(
                PluginSettings $settings,
            ): string {
                unset($settings);

                return 'token';
            }
        };
    }
}
