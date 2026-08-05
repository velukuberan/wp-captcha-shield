<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\LostPassword;

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
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormIntegration;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class LostPasswordFormRegistrarTest extends TestCase
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

    public function testItRegistersLostPasswordHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_action')
            ->once()
            ->with(
                'login_enqueue_scripts',
                [$integration, 'enqueue'],
            );

        Functions\expect('add_action')
            ->once()
            ->with(
                'lostpassword_form',
                [$integration, 'render'],
            );

        Functions\expect('add_action')
            ->once()
            ->with(
                'lostpassword_post',
                [$integration, 'validate'],
                30,
                2,
            );

        (new LostPasswordFormRegistrar($integration))->registerHooks();
    }

    private function integration(): LostPasswordFormIntegration
    {
        $repository = Mockery::mock(SettingsRepository::class);
        $httpClient = Mockery::mock(HttpClient::class);

        return new LostPasswordFormIntegration(
            $repository,
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory($httpClient),
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
