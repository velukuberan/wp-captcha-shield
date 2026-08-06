<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\Registration;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WP_Error;
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
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormIntegration;

final class WooCommerceRegistrationFormIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
        $_POST = [];
        $_SERVER = [];
        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SERVER = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItEnqueuesAndRendersThroughTheSharedWidgetRenderer(): void
    {
        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        )->render();

        self::assertSame('render', $widget->operation);
        self::assertSame('woocommerce_registration', $widget->action);
        self::assertSame('woocommerce-registration-form', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItDoesNotRenderWhenCaptchaIsDisabled(): void
    {
        $widget = $this->recordingWidget();

        $this->integration(
            PluginSettings::defaults(),
            $widget,
            $this->nonCallingHttpClient(),
        )->render();

        self::assertNull($widget->operation);
    }

    public function testFormOverrideCanDisableWooCommerceRegistrationCaptcha(): void
    {
        $settings = new PluginSettings(
            GlobalCaptchaSetting::provider(
                CaptchaProvider::CloudflareTurnstile,
            ),
            [
                SupportedForms::WOOCOMMERCE_REGISTRATION =>
                    FormCaptchaSetting::disabled(),
            ],
            new TurnstileSettings(
                'turnstile-site-key',
                'turnstile-secret-key',
                CloudflareTurnstileMode::Managed,
            ),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );
        $widget = $this->recordingWidget();

        $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        )->render();

        self::assertNull($widget->operation);
    }

    public function testItPreservesExistingWooCommerceErrorsWithoutVerification(): void
    {
        $errors = new WP_Error(
            'existing_error',
            'Existing WooCommerce error.',
        );

        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');

        $result = $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(
            $errors,
            'username',
            'user@example.com',
        );

        self::assertSame($errors, $result);
        self::assertSame(
            'existing_error',
            $result->get_error_code(),
        );
    }

    public function testItBypassesVerificationWhenCaptchaIsDisabled(): void
    {
        $errors = new WP_Error();

        $result = $this->integration(
            PluginSettings::defaults(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($errors, 'username', 'user@example.com');

        self::assertSame($errors, $result);
        self::assertFalse($result->has_errors());
    }

    public function testItPreservesErrorsWhenVerificationSucceeds(): void
    {
        $this->submitRegistration('valid-token');
        $httpClient = $this->successfulHttpClient();
        $errors = new WP_Error();

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate($errors, 'username', 'user@example.com');

        self::assertSame($errors, $result);
        self::assertFalse($result->has_errors());
    }

    public function testItFailsClosedWhenTheTokenIsMissing(): void
    {
        $this->submitRegistration();

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(new WP_Error(), 'username', 'user@example.com');

        self::assertSame(
            'CAPTCHA verification failed. Please try again.',
            $result->get_error_message(
                'wp_captcha_shield_verification_failed',
            ),
        );
    }

    public function testItReturnsTheUnavailableVisitorMessage(): void
    {
        $this->submitRegistration('submitted-token');
        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Provider request failed.'));

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate(new WP_Error(), 'username', 'user@example.com');

        self::assertSame(
            'CAPTCHA verification is temporarily unavailable. Please try again.',
            $result->get_error_message(
                'wp_captcha_shield_verification_failed',
            ),
        );
    }

    public function testItReturnsTheMisconfiguredVisitorMessage(): void
    {
        $this->submitRegistration('submitted-token');
        $settings = new PluginSettings(
            GlobalCaptchaSetting::provider(
                CaptchaProvider::CloudflareTurnstile,
            ),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );

        $result = $this->integration(
            $settings,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(new WP_Error(), 'username', 'user@example.com');

        self::assertSame(
            'CAPTCHA verification could not be completed. Please contact the site administrator.',
            $result->get_error_message(
                'wp_captcha_shield_verification_failed',
            ),
        );
    }

    public function testItLoadsSettingsOnlyOncePerIntegrationInstance(): void
    {
        $settings = $this->enabledSettings();
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldReceive('load')->once()->andReturn($settings);
        $widget = $this->recordingWidget();
        $integration = $this->integrationWithRepository(
            $repository,
            $widget,
            $this->nonCallingHttpClient(),
        );

        $integration->render();
        $integration->render();

        self::assertSame('render', $widget->operation);
    }

    private function integration(
        PluginSettings $settings,
        RecordingCaptchaWidget $widget,
        HttpClient $httpClient,
    ): WooCommerceRegistrationFormIntegration {
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
    ): WooCommerceRegistrationFormIntegration {
        return new WooCommerceRegistrationFormIntegration(
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
                SupportedForms::WOOCOMMERCE_REGISTRATION =>
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

    private function successfulHttpClient(): HttpClient
    {
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
            ->andReturn(
                new HttpResponse(
                    200,
                    json_encode(
                        [
                            'success' => true,
                            'action' => 'woocommerce_registration',
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ),
            );

        return $httpClient;
    }

    private function submitRegistration(?string $token = null): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

        if ($token !== null) {
            $_POST['recorded-token'] = $token;
        }
    }
}
