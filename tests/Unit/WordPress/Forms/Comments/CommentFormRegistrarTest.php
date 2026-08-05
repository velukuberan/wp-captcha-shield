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
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha\RecordingCaptchaWidget;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormIntegration;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormRegistrar;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class CommentFormRegistrarTest extends TestCase
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

    public function testItRegistersCommentHooks(): void
    {
        $integration = $this->integration();

        Functions\expect('add_action')
            ->once()
            ->with('wp_enqueue_scripts', [$integration, 'enqueue']);

        Functions\expect('add_filter')
            ->once()
            ->with(
                'comment_form_submit_field',
                [$integration, 'addWidgetToSubmitField'],
            );

        Functions\expect('add_action')
            ->once()
            ->with(
                'pre_comment_on_post',
                [$integration, 'validate'],
                30,
                1,
            );

        (new CommentFormRegistrar($integration))->registerHooks();
    }

    private function integration(): CommentFormIntegration
    {
        return new CommentFormIntegration(
            Mockery::mock(SettingsRepository::class),
            new EffectiveCaptchaProviderResolver(),
            new CaptchaProviderConfigurationFactory(),
            new CaptchaServiceFactory(Mockery::mock(HttpClient::class)),
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
