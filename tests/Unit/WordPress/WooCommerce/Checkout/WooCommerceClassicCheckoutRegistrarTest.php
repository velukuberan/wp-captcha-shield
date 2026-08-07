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
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceClassicCheckoutIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceClassicCheckoutRegistrar;

final class WooCommerceClassicCheckoutRegistrarTest extends TestCase
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

    public function testItRegistersClassicCheckoutHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_action')
            ->once()
            ->with(
                'woocommerce_checkout_before_order_review',
                [$integration, 'render'],
            );
        Functions\expect('add_action')
            ->once()
            ->with(
                'woocommerce_after_checkout_validation',
                [$integration, 'validate'],
                10,
                2,
            );

        (new WooCommerceClassicCheckoutRegistrar(
            $integration,
        ))->registerHooks();
    }

    private function integration(): WooCommerceClassicCheckoutIntegration
    {
        return new WooCommerceClassicCheckoutIntegration(
            Mockery::mock(SettingsRepository::class),
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory(Mockery::mock(HttpClient::class)),
            new CaptchaWidgetRenderer(new CaptchaWidgetResolver([])),
        );
    }
}
