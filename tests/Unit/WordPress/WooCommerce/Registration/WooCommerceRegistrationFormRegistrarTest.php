<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\Registration;

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
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormRegistrar;

final class WooCommerceRegistrationFormRegistrarTest extends TestCase
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

    public function testItRegistersWooCommerceRegistrationHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_action')
            ->once()
            ->with(
                'woocommerce_register_form',
                [$integration, 'render'],
            );

        Functions\expect('add_filter')
            ->once()
            ->with(
                'woocommerce_registration_errors',
                [$integration, 'validate'],
                10,
                3,
            );

        (new WooCommerceRegistrationFormRegistrar(
            $integration,
        ))->registerHooks();
    }

    private function integration(): WooCommerceRegistrationFormIntegration
    {
        return new WooCommerceRegistrationFormIntegration(
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
