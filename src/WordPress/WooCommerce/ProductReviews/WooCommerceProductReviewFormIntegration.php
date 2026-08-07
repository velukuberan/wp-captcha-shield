<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\ProductReviews;

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationResult;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class WooCommerceProductReviewFormIntegration
{
    private const CAPTCHA_ACTION = 'woocommerce_product_review';

    private const FORM_ID = 'commentform';

    private const PRODUCT_POST_TYPE = 'product';

    private ?PluginSettings $settings = null;

    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly EffectiveCaptchaProviderResolver $providerResolver,
        private readonly CaptchaProviderConfigurationFactory $configurationFactory,
        private readonly CaptchaServiceFactory $serviceFactory,
        private readonly CaptchaWidgetRenderer $widgetRenderer,
    ) {
    }

    public function enqueue(): void
    {
        if (!$this->isProductReviewPage()) {
            return;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return;
        }

        $this->widgetRenderer->enqueue(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );
    }

    public function addWidgetToSubmitField(string $submitField): string
    {
        if (!$this->isProductReviewPage()) {
            return $submitField;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return $submitField;
        }

        ob_start();

        $this->widgetRenderer->render(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );

        $widget = ob_get_clean();

        return ($widget === false ? '' : $widget) . $submitField;
    }

    public function validate(int $commentPostId): void
    {
        if (!$this->isProductPost($commentPostId)) {
            return;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return;
        }

        $result = $this->serviceFactory
            ->create(
                $this->configurationFactory->create($this->settings()),
            )
            ->verify(
                $effectiveProvider,
                new CaptchaVerificationRequest(
                    $this->submittedToken($effectiveProvider),
                    $this->serverValue('REMOTE_ADDR'),
                    $this->serverValue('HTTP_USER_AGENT'),
                    self::CAPTCHA_ACTION,
                ),
            );

        if ($result->isSuccessful()) {
            return;
        }

        wp_die(
            esc_html($this->visitorMessage($result)),
            esc_html__(
                'Review submission blocked',
                'wp-captcha-shield',
            ),
            [
                'response' => 403,
                'back_link' => true,
            ],
        );
    }

    private function widgetContext(): CaptchaWidgetContext
    {
        return new CaptchaWidgetContext(
            self::CAPTCHA_ACTION,
            self::FORM_ID,
        );
    }

    private function settings(): PluginSettings
    {
        return $this->settings ??= $this->repository->load();
    }

    private function effectiveProvider(): EffectiveCaptchaProvider
    {
        $settings = $this->settings();
        $formSetting = $settings->formSettings()[SupportedForms::WOOCOMMERCE_PRODUCT_REVIEWS]
            ?? FormCaptchaSetting::useDefault();

        return $this->providerResolver->resolve(
            $settings->globalSetting(),
            $formSetting,
        );
    }

    /**
     * WooCommerce product reviews use the native WordPress comment form and
     * do not add a separate review nonce. The provider token is therefore read
     * during the normal comment submission request.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Missing

    private function submittedToken(
        EffectiveCaptchaProvider $effectiveProvider,
    ): string {
        $field = $this->widgetRenderer->tokenFieldName(
            $effectiveProvider,
            $this->settings(),
        );

        if (
            $field === ''
            || !isset($_POST[$field])
            || !is_string($_POST[$field])
        ) {
            return '';
        }

        return sanitize_text_field(
            wp_unslash($_POST[$field]),
        );
    }

    // phpcs:enable WordPress.Security.NonceVerification.Missing

    private function serverValue(string $key): ?string
    {
        if (!isset($_SERVER[$key]) || !is_string($_SERVER[$key])) {
            return null;
        }

        return sanitize_text_field(wp_unslash($_SERVER[$key]));
    }

    private function isProductReviewPage(): bool
    {
        return is_singular(self::PRODUCT_POST_TYPE)
            && comments_open();
    }

    private function isProductPost(int $postId): bool
    {
        return get_post_type($postId) === self::PRODUCT_POST_TYPE;
    }

    private function visitorMessage(VerificationResult $result): string
    {
        if ($result->isUnavailable()) {
            return __(
                'CAPTCHA verification is temporarily unavailable. Please try again.',
                'wp-captcha-shield',
            );
        }

        if ($result->isMisconfigured()) {
            return __(
                'CAPTCHA verification could not be completed. Please contact the site administrator.',
                'wp-captcha-shield',
            );
        }

        return __(
            'CAPTCHA verification failed. Please try again.',
            'wp-captcha-shield',
        );
    }
}
