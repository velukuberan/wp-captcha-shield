<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms;

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
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormIntegration;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormIntegration;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormRegistrar;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormIntegration;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Registration\RegistrationFormIntegration;
use WpCaptchaShield\WordPress\Forms\Registration\RegistrationFormRegistrar;
use WpCaptchaShield\WordPress\Forms\WordPressFormsBootstrap;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class WordPressFormsBootstrapTest extends TestCase
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

    public function testItRegistersHooksForAllFourCoreForms(): void
    {
        Functions\expect('add_action')
            ->once()
            ->with('login_enqueue_scripts', Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with('login_form', Mockery::type('array'));
        Functions\expect('add_filter')
            ->once()
            ->with('authenticate', Mockery::type('array'), 30, 3);

        Functions\expect('add_action')
            ->once()
            ->with('login_enqueue_scripts', Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with('register_form', Mockery::type('array'));
        Functions\expect('add_filter')
            ->once()
            ->with('registration_errors', Mockery::type('array'), 30, 3);

        Functions\expect('add_action')
            ->once()
            ->with('login_enqueue_scripts', Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with('lostpassword_form', Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with('lostpassword_post', Mockery::type('array'), 30, 2);

        Functions\expect('add_action')
            ->once()
            ->with('wp_enqueue_scripts', Mockery::type('array'));
        Functions\expect('add_filter')
            ->once()
            ->with('comment_form_submit_field', Mockery::type('array'));
        Functions\expect('add_action')
            ->once()
            ->with('pre_comment_on_post', Mockery::type('array'), 30, 1);

        $this->bootstrap()->registerHooks();
        $this->addToAssertionCount(1);
    }

    private function bootstrap(): WordPressFormsBootstrap
    {
        $repository = Mockery::mock(SettingsRepository::class);
        $httpClient = Mockery::mock(HttpClient::class);
        $providerResolver = new EffectiveCaptchaProviderResolver();
        $configurationFactory = new CaptchaProviderConfigurationFactory();
        $serviceFactory = new CaptchaServiceFactory($httpClient);
        $widgetRenderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([]),
        );

        $loginIntegration = new LoginFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );
        $registrationIntegration = new RegistrationFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );
        $lostPasswordIntegration = new LostPasswordFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );
        $commentIntegration = new CommentFormIntegration(
            $repository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
            ['product'],
        );

        return new WordPressFormsBootstrap(
            new LoginFormRegistrar($loginIntegration),
            new RegistrationFormRegistrar($registrationIntegration),
            new LostPasswordFormRegistrar($lostPasswordIntegration),
            new CommentFormRegistrar($commentIntegration),
        );
    }
}
