<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\LostPassword;

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
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormIntegration;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class LostPasswordFormIntegrationTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();

        $_POST = [];
        $_SERVER = [];
        $GLOBALS['action'] = 'lostpassword';

        Functions\when('wp_unslash')->returnArg();
        Functions\when('sanitize_text_field')->returnArg();
        Functions\when('__')->returnArg();
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SERVER = [];
        unset($GLOBALS['action']);

        Monkey\tearDown();

        parent::tearDown();
    }

    public function testItEnqueuesLostPasswordAssetsAndTheSharedWidget(): void
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

        $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        )->enqueue();

        self::assertSame('enqueue', $widget->operation);
        self::assertSame('wordpress_lost_password', $widget->action);
        self::assertSame('lostpasswordform', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItEnqueuesOnTheRetrievePasswordAlias(): void
    {
        $GLOBALS['action'] = 'retrievepassword';

        Functions\expect('wp_enqueue_style')->once();

        $widget = $this->recordingWidget();

        $this->integration(
            $this->enabledSettings(),
            $widget,
            $this->nonCallingHttpClient(),
        )->enqueue();

        self::assertSame('enqueue', $widget->operation);
    }

    public function testItDoesNotEnqueueOnAnotherLoginScreen(): void
    {
        $GLOBALS['action'] = 'login';
        $widget = $this->recordingWidget();
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');

        Functions\expect('wp_enqueue_style')->never();

        $this->integrationWithRepository(
            $repository,
            $widget,
            $this->nonCallingHttpClient(),
        )->enqueue();

        self::assertNull($widget->operation);
    }

    public function testItDoesNotEnqueueWhenCaptchaIsDisabled(): void
    {
        $widget = $this->recordingWidget();

        Functions\expect('wp_enqueue_style')->never();

        $this->integration(
            PluginSettings::defaults(),
            $widget,
            $this->nonCallingHttpClient(),
        )->enqueue();

        self::assertNull($widget->operation);
    }

    public function testItRendersThroughTheSharedWidgetRenderer(): void
    {
        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        $this->integration(
            $settings,
            $widget,
            $this->nonCallingHttpClient(),
        )->render();

        self::assertSame('render', $widget->operation);
        self::assertSame('wordpress_lost_password', $widget->action);
        self::assertSame('lostpasswordform', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItPreservesExistingLostPasswordErrors(): void
    {
        $errors = new WP_Error('existing_error', 'Existing error.');
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');

        $this->integrationWithRepository(
            $repository,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($errors, false);

        self::assertSame('existing_error', $errors->get_error_code());
    }

    public function testItBypassesVerificationWhenCaptchaIsDisabled(): void
    {
        $errors = new WP_Error();

        $this->integration(
            PluginSettings::defaults(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($errors, false);

        self::assertFalse($errors->has_errors());
    }

    public function testItLeavesErrorsEmptyWhenVerificationSucceeds(): void
    {
        $this->submitLostPassword('valid-token');
        $errors = new WP_Error();

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
                            'action' => 'wordpress_lost_password',
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ),
            );

        $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate($errors, false);

        self::assertFalse($errors->has_errors());
    }

    public function testItFailsClosedWhenTheTokenIsMissing(): void
    {
        $this->submitLostPassword();
        $errors = new WP_Error();

        $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($errors, false);

        self::assertSame(
            'wp_captcha_shield_verification_failed',
            $errors->get_error_code(),
        );
        self::assertSame(
            'CAPTCHA verification failed. Please try again.',
            $errors->get_error_message(),
        );
    }

    public function testItReturnsTheUnavailableVisitorMessage(): void
    {
        $this->submitLostPassword('submitted-token');
        $errors = new WP_Error();

        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Provider request failed.'));

        $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate($errors, false);

        self::assertSame(
            'CAPTCHA verification is temporarily unavailable. Please try again.',
            $errors->get_error_message(),
        );
    }

    public function testItReturnsTheMisconfiguredVisitorMessage(): void
    {
        $this->submitLostPassword('submitted-token');
        $errors = new WP_Error();

        $settings = new PluginSettings(
            GlobalCaptchaSetting::provider(
                CaptchaProvider::CloudflareTurnstile,
            ),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );

        $this->integration(
            $settings,
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate($errors, false);

        self::assertSame(
            'CAPTCHA verification could not be completed. Please contact the site administrator.',
            $errors->get_error_message(),
        );
    }

    public function testItLoadsSettingsOnlyOncePerIntegrationInstance(): void
    {
        $settings = $this->enabledSettings();
        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldReceive('load')->once()->andReturn($settings);

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
    ): LostPasswordFormIntegration {
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
    ): LostPasswordFormIntegration {
        return new LostPasswordFormIntegration(
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
                SupportedForms::WORDPRESS_LOST_PASSWORD =>
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

    private function submitLostPassword(?string $token = null): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

        if ($token !== null) {
            $_POST['recorded-token'] = $token;
        }
    }
}
