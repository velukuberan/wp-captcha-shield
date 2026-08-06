<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\Login;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormRegistrar;

final class WooCommerceLoginFormRegistrarTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRegistersWooCommerceLoginHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_action')
            ->once()
            ->with(
                'woocommerce_login_form',
                [$integration, 'render'],
            );

        Functions\expect('add_filter')
            ->once()
            ->with(
                'woocommerce_process_login_errors',
                [$integration, 'validate'],
                10,
                3,
            );

        (new WooCommerceLoginFormRegistrar(
            $integration,
        ))->registerHooks();
    }

    private function integration(): WooCommerceLoginFormIntegration
    {
        return new WooCommerceLoginFormIntegration(
            Mockery::mock(SettingsRepository::class),
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory(
                Mockery::mock(HttpClient::class),
            ),
            new CaptchaWidgetRenderer(
                new CaptchaWidgetResolver([
                    new RecordingCaptchaWidget(
                        CaptchaProvider::CloudflareTurnstile,
                    ),
                ]),
            ),
        );
    }
}
