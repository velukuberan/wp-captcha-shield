<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Login;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use stdClass;
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
use WpCaptchaShield\WordPress\Forms\Login\LoginFormIntegration;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class LoginFormIntegrationTest extends TestCase
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

    public function testItEnqueuesLoginAssetsAndTheSharedWidget(): void
    {
        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        Functions\expect('wp_enqueue_style')
            ->once()
            ->with(
                'wp-captcha-shield-login',
                WP_CAPTCHA_SHIELD_URL . 'assets/css/login.css',
                [],
                WP_CAPTCHA_SHIELD_VERSION,
            );

        $integration = $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        );

        $integration->enqueue();

        self::assertSame('enqueue', $widget->operation);
        self::assertSame('wordpress_login', $widget->action);
        self::assertSame('loginform', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItDoesNotEnqueueAssetsWhenCaptchaIsDisabled(): void
    {
        $widget = $this->recordingWidget();

        Functions\expect('wp_enqueue_style')->never();

        $integration = $this->integration(
            PluginSettings::defaults(),
            $widget,
            $this->nonCallingHttpClient(),
        );

        $integration->enqueue();

        self::assertNull($widget->operation);
        self::assertNull($widget->action);
        self::assertNull($widget->formId);
    }

    public function testItRendersThroughTheSharedWidgetRenderer(): void
    {
        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        $integration = $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        );

        $integration->render();

        self::assertSame('render', $widget->operation);
        self::assertSame('wordpress_login', $widget->action);
        self::assertSame('loginform', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItPreservesTheUserForNonLoginRequests(): void
    {
        $user = new stdClass();

        $_SERVER['REQUEST_METHOD'] = 'GET';

        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');

        $integration = $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        );

        self::assertSame(
            $user,
            $integration->authenticate(
                $user,
                'username',
                'password',
            ),
        );
    }

    public function testItPreservesAnExistingWordPressError(): void
    {
        $error = new WP_Error(
            'existing_error',
            'Existing authentication error.',
        );

        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');

        $integration = $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        );

        self::assertSame(
            $error,
            $integration->authenticate(
                $error,
                'username',
                'password',
            ),
        );
    }

    public function testItBypassesVerificationWhenCaptchaIsDisabled(): void
    {
        $user = new stdClass();

        $this->submitLogin('ignored-token');

        $integration = $this->integration(
            PluginSettings::defaults(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        );

        self::assertSame(
            $user,
            $integration->authenticate(
                $user,
                'username',
                'password',
            ),
        );
    }

    public function testItPreservesTheUserWhenVerificationSucceeds(): void
    {
        $user = new stdClass();

        $this->submitLogin('valid-token');

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
                            'action' => 'wordpress_login',
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ),
            );

        $integration = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        );

        self::assertSame(
            $user,
            $integration->authenticate(
                $user,
                'username',
                'password',
            ),
        );
    }

    public function testItFailsClosedWhenTheTokenIsMissing(): void
    {
        $this->submitLogin();

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->authenticate(
            new stdClass(),
            'username',
            'password',
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(
            'wp_captcha_shield_verification_failed',
            $result->get_error_code(),
        );
        self::assertSame(
            'CAPTCHA verification failed. Please try again.',
            $result->get_error_message(),
        );
    }

    public function testItReturnsTheUnavailableVisitorMessage(): void
    {
        $this->submitLogin('submitted-token');

        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(
                new HttpClientException(
                    'Provider request failed.',
                ),
            );

        $result = $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->authenticate(
            new stdClass(),
            'username',
            'password',
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(
            'CAPTCHA verification is temporarily unavailable. Please try again.',
            $result->get_error_message(),
        );
    }

    public function testItReturnsTheMisconfiguredVisitorMessage(): void
    {
        $this->submitLogin('submitted-token');

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
        )->authenticate(
            new stdClass(),
            'username',
            'password',
        );

        self::assertInstanceOf(WP_Error::class, $result);
        self::assertSame(
            'CAPTCHA verification could not be completed. Please contact the site administrator.',
            $result->get_error_message(),
        );
    }

    public function testItLoadsSettingsOnlyOncePerIntegrationInstance(): void
    {
        $settings = $this->enabledSettings();
        $repository = Mockery::mock(SettingsRepository::class);

        $repository
            ->shouldReceive('load')
            ->once()
            ->andReturn($settings);

        Functions\expect('wp_enqueue_style')->once();

        $widget = $this->recordingWidget();

        $integration = $this->integrationWithRepository(
            $repository,
            $widget,
            $this->nonCallingHttpClient(),
        );

        $integration->render();
        $integration->enqueue();

        self::assertSame($settings, $widget->settings);
    }

    private function integration(
        PluginSettings $settings,
        RecordingCaptchaWidget $widget,
        HttpClient $httpClient,
    ): LoginFormIntegration {
        $repository = Mockery::mock(SettingsRepository::class);

        $repository
            ->shouldReceive('load')
            ->once()
            ->andReturn($settings);

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
    ): LoginFormIntegration {
        return new LoginFormIntegration(
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
                SupportedForms::WORDPRESS_LOGIN =>
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

    private function submitLogin(?string $token = null): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

        $_POST['log'] = 'username';
        $_POST['pwd'] = 'password';

        if ($token !== null) {
            $_POST['recorded-token'] = $token;
        }
    }
}
