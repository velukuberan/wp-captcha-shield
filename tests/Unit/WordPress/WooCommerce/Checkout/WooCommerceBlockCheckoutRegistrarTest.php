<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\Checkout;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutRegistrar;

final class WooCommerceBlockCheckoutRegistrarTest extends TestCase
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

    public function testItRegistersBlockCheckoutHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_filter')
            ->once()
            ->with(
                'render_block_woocommerce/checkout-actions-block',
                [$integration, 'render'],
            );
        Functions\expect('add_filter')
            ->once()
            ->with(
                'rest_pre_dispatch',
                [$integration, 'validate'],
                10,
                3,
            );

        (new WooCommerceBlockCheckoutRegistrar(
            $integration,
        ))->registerHooks();
    }

    private function integration(): WooCommerceBlockCheckoutIntegration
    {
        return new WooCommerceBlockCheckoutIntegration(
            Mockery::mock(SettingsRepository::class),
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory(Mockery::mock(HttpClient::class)),
            new CaptchaWidgetRenderer(new CaptchaWidgetResolver([])),
        );
    }
}
