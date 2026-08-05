<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Comments;

use Brain\Monkey;
use Brain\Monkey\Functions;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormIntegration;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class CommentFormIntegrationTest extends TestCase
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
        Functions\when('esc_html')->returnArg();

        Functions\when('esc_html__')->alias(function (
            string $text,
            string $domain,
        ): string {
            unset($domain);

            return $text;
        });
    }

    protected function tearDown(): void
    {
        $_POST = [];
        $_SERVER = [];
        Monkey\tearDown();
        parent::tearDown();
    }

    public function testItEnqueuesTheSharedWidgetOnACommentFormPage(): void
    {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('comments_open')->justReturn(true);
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('post_type_supports')->justReturn(true);

        $widget = $this->recordingWidget();
        $settings = $this->enabledSettings();

        $this->integration($settings, $widget, $this->nonCallingHttpClient())
            ->enqueue();

        self::assertSame('enqueue', $widget->operation);
        self::assertSame('wordpress_comment', $widget->action);
        self::assertSame('commentform', $widget->formId);
        self::assertSame($settings, $widget->settings);
    }

    public function testItDoesNotEnqueueWhenCommentsAreClosed(): void
    {
        Functions\when('is_singular')->justReturn(true);
        Functions\when('comments_open')->justReturn(false);

        $repository = Mockery::mock(SettingsRepository::class);
        $repository->shouldNotReceive('load');
        $widget = $this->recordingWidget();

        $this->integrationWithRepository(
            $repository,
            $widget,
            $this->nonCallingHttpClient(),
        )->enqueue();

        self::assertNull($widget->operation);
    }

    public function testItPrependsTheWidgetToTheSubmitField(): void
    {
        $widget = $this->recordingWidget();

        $submitField = '<p class="form-submit">Submit</p>';
        $result = $this->integration(
            $this->enabledSettings(),
            $widget,
            $this->nonCallingHttpClient(),
        )->addWidgetToSubmitField($submitField);

        self::assertSame('render', $widget->operation);
        self::assertStringEndsWith($submitField, $result);
    }

    public function testItPreservesTheSubmitFieldWhenCaptchaIsDisabled(): void
    {
        $submitField = '<p class="form-submit">Submit</p>';

        $result = $this->integration(
            PluginSettings::defaults(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->addWidgetToSubmitField($submitField);

        self::assertSame($submitField, $result);
    }

    public function testItAllowsTheCommentWhenVerificationSucceeds(): void
    {
        $_POST['recorded-token'] = 'valid-token';
        $_SERVER['REMOTE_ADDR'] = '192.0.2.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Browser';

        $httpClient = Mockery::mock(HttpClient::class);
        $httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn(new HttpResponse(
                200,
                json_encode(
                    [
                        'success' => true,
                        'action' => 'wordpress_comment',
                    ],
                    JSON_THROW_ON_ERROR,
                ),
            ));

        Functions\expect('wp_die')->never();

        $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $httpClient,
        )->validate(123);
    }

    public function testItFailsClosedWhenTheTokenIsMissing(): void
    {
        Functions\expect('wp_die')
            ->once()
            ->with(
                'CAPTCHA verification failed. Please try again.',
                'Comment submission blocked',
                [
                    'response' => 403,
                    'back_link' => true,
                ],
            );

        $this->integration(
            $this->enabledSettings(),
            $this->recordingWidget(),
            $this->nonCallingHttpClient(),
        )->validate(123);
    }

    private function integration(
        PluginSettings $settings,
        RecordingCaptchaWidget $widget,
        HttpClient $httpClient,
    ): CommentFormIntegration {
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
    ): CommentFormIntegration {
        return new CommentFormIntegration(
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
                SupportedForms::WORDPRESS_COMMENTS =>
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
}
