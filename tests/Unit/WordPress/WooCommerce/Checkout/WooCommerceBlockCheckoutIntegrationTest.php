<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\Checkout;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WP_Error;
use WP_REST_Request;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutIntegration;

final class WooCommerceBlockCheckoutIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $_SERVER = [];

        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('esc_attr')->returnArg();
        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        $_SERVER = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItRendersBeforeTheCheckoutActionsBlock(): void
    {
        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        Functions\expect('wp_enqueue_script')
            ->once()
            ->with(
                'wp-captcha-shield-woocommerce-block-checkout',
                WP_CAPTCHA_SHIELD_URL
                . 'assets/js/woocommerce-block-checkout.js',
                ['wp-data', 'wc-blocks-data-store'],
                WP_CAPTCHA_SHIELD_VERSION,
                true,
            );

        $output = $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        )->render('<div>Checkout actions</div>');

        self::assertStringStartsWith(
            '<div class="wp-captcha-shield-block-checkout"',
            $output,
        );
        self::assertStringContainsString(
            'data-token-field="recorded-token"',
            $output,
        );
        self::assertStringEndsWith('<div>Checkout actions</div>', $output);
        self::assertSame('woocommerce_checkout', $widget->action);
        self::assertSame('woocommerce-block-checkout', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItDoesNotRenderWhenCheckoutCaptchaIsDisabled(): void
    {
        Functions\expect('wp_enqueue_script')->never();
        $widget = $this->recordingWidget();

        $output = $this->integration(
            PluginSettings::defaults(),
            $widget,
            $this->nonCallingHttpClient(),
        )->render('<div>Checkout actions</div>');

        self::assertSame('<div>Checkout actions</div>', $output);
        self::assertNull($widget->operation);
    }

    public function testItPreservesAnExistingRestPreDispatchResult(): void
    {
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');
        $request = Mockery::mock(WP_REST_Request::class);
        $request->shouldNotReceive('get_method');
        $existing = new WP_Error('existing_error', 'Existing REST error.');

        $result = $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($existing, null, $request);

        self::assertSame($existing, $result);
    }

    public function testItIgnoresNonCheckoutStoreApiRequests(): void
    {
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');
        $request = $this->request(
            'POST',
            '/wc/store/v1/cart',
            [],
        );

        $result = $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(null, null, $request);

        self::assertNull($result);
    }

    public function testItBypassesVerificationWhenCheckoutCaptchaIsDisabled(): void
    {
        $request = $this->request(
            'POST',
            '/wc/store/v1/checkout',
            [],
        );

        $result = $this->integration(
            PluginSettings::defaults(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(null, null, $request);

        self::assertNull($result);
    }

    public function testItAllowsBlockCheckoutWhenVerificationSucceeds(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                'https://challenges.cloudflare.com/turnstile/v0/siteverify',
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'turnstile-secret-key',
                        'response' => 'valid-token',
                        'remoteip' => '192.0.2.10',
                    ],
                ],
            )
            ->andReturn(new HttpResponse(
                200,
                json_encode(
                    [
                        'success' => true,
                        'action' => 'woocommerce_checkout',
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ));

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate(
            null,
            null,
            $this->request(
                'POST',
                '/wc/store/v1/checkout',
                [
                    'wp-captcha-shield' => [
                        'token' => 'valid-token',
                    ],
                ],
            ),
        );

        self::assertNull($result);
    }

    public function testItFailsClosedWhenBlockCheckoutTokenIsMissing(): void
    {
        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(
            null,
            null,
            $this->request('POST', '/wc/store/v1/checkout', []),
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(
            'CAPTCHA verification failed. Please try again.',
            $result->get_error_message(
                'wp_captcha_shield_verification_failed',
            ),
        );
    }

    public function testItReturnsTheUnavailableVisitorMessage(): void
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Provider request failed.'));

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate(
            null,
            null,
            $this->request(
                'POST',
                '/wc/store/v1/checkout',
                [
                    'wp-captcha-shield' => [
                        'token' => 'submitted-token',
                    ],
                ],
            ),
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(
            'CAPTCHA verification is temporarily unavailable. Please try again.',
            $result->get_error_message(
                'wp_captcha_shield_verification_failed',
            ),
        );
    }

    private function integration(
        PluginSettings $settings,
        RecordingCaptchaWidget $widget,
        HttpClient $httpClient,
    ): WooCommerceBlockCheckoutIntegration {
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldReceive('load')->once()->andReturn($settings);

        return $this->integrationWithRepository(
            $repository,
            $widget,
            $httpClient,
        );
    }

    private function integrationWithRepository(
        SettingsRepository $repository,
        RecordingCaptchaWidget $widget,
        HttpClient $httpClient,
    ): WooCommerceBlockCheckoutIntegration {
        return new WooCommerceBlockCheckoutIntegration(
            $repository,
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory($httpClient),
            new CaptchaWidgetRenderer(
                new CaptchaWidgetResolver([$widget]),
            ),
        );
    }

    private function recordingWidget(): RecordingCaptchaWidget
    {
        return new RecordingCaptchaWidget(
            CaptchaProvider::CloudflareTurnstile,
        );
    }

    private function enabledSettings(): PluginSettings
    {
        return new PluginSettings(
            GlobalCaptchaSetting::provider(
                CaptchaProvider::CloudflareTurnstile,
            ),
            [
                SupportedForms::WOOCOMMERCE_CHECKOUT =>
                    FormCaptchaSetting::useDefault(),
            ],
            new TurnstileSettings(
                'turnstile-site-key',
                'turnstile-secret-key',
                CloudflareTurnstileMode::Managed,
            ),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );
    }

    private function nonCallingHttpClient(): HttpClient
    {
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient->shouldNotReceive('post');

        return $httpClient;
    }

    /**
     * @param array<string, mixed> $extensions
     */
    private function request(
        string $method,
        string $route,
        array $extensions,
    ): WP_REST_Request {
        $request = Mockery::mock(WP_REST_Request::class);
        $request->shouldReceive('get_method')->andReturn($method);
        $request->shouldReceive('get_route')->andReturn($route);
        $request
            ->shouldReceive('get_param')
            ->with('extensions')
            ->andReturn($extensions);

        return $request;
    }
}
