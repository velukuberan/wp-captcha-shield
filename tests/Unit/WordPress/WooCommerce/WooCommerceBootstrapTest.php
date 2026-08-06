<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceAvailability;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceBootstrap;

final class WooCommerceBootstrapTest extends TestCase
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

    public function testItRegistersInitializationAfterPluginsAreLoaded(): void
    {
        $bootstrap = $this->bootstrap();

        Functions\expect('add_action')
            ->once()
            ->with('plugins_loaded', [$bootstrap, 'initialize']);

        $bootstrap->registerHooks();
        $this->addToAssertionCount(1);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItDoesNothingWhenWooCommerceIsUnavailable(): void
    {
        Functions\expect('add_action')->never();
        Functions\expect('add_filter')->never();

        $this->bootstrap()->initialize();

        self::assertFalse(class_exists('WooCommerce'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testItRegistersAllHooksWhenWooCommerceIsAvailable(): void
    {
        eval('class WooCommerce {}');

        Functions\expect('add_action')
            ->once()
            ->with('woocommerce_login_form', Mockery::type('array'));
        Functions\expect('add_filter')
            ->once()
            ->with(
                'woocommerce_process_login_errors',
                Mockery::type('array'),
                10,
                3,
            );
        Functions\expect('add_action')
            ->once()
            ->with('woocommerce_register_form', Mockery::type('array'));
        Functions\expect('add_filter')
            ->once()
            ->with(
                'woocommerce_registration_errors',
                Mockery::type('array'),
                10,
                3,
            );

        $this->bootstrap()->initialize();

        self::assertTrue(class_exists('WooCommerce'));
    }

    private function bootstrap(): WooCommerceBootstrap
    {
        $repository = Mockery::mock(SettingsRepository::class);
        $httpClient = Mockery::mock(HttpClient::class);
        $providerResolver = new EffectiveCaptchaProviderResolver();
        $configurationFactory = new CaptchaProviderConfigurationFactory();
        $serviceFactory = new CaptchaServiceFactory($httpClient);
        $widgetRenderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([]),
        );

        $loginIntegration = new WooCommerceLoginFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );
        $registrationIntegration = new WooCommerceRegistrationFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        return new WooCommerceBootstrap(
            new WooCommerceAvailability(),
            new WooCommerceLoginFormRegistrar($loginIntegration),
            new WooCommerceRegistrationFormRegistrar(
                $registrationIntegration,
            ),
        );
    }
}
