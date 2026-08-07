<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\WooCommerce\ProductReviews;

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
use WpCaptchaShield\WordPress\WooCommerce\ProductReviews\WooCommerceProductReviewFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\ProductReviews\WooCommerceProductReviewFormRegistrar;

final class WooCommerceProductReviewFormRegistrarTest extends TestCase
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

    public function testItRegistersProductReviewHooks(): void
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

        (new WooCommerceProductReviewFormRegistrar(
            $integration,
        ))->registerHooks();
    }

    private function integration(): WooCommerceProductReviewFormIntegration
    {
        return new WooCommerceProductReviewFormIntegration(
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
