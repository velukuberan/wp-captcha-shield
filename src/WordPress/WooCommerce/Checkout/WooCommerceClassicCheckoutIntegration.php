<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Checkout;

use WP_Error;
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

final class WooCommerceClassicCheckoutIntegration
{
    private const CAPTCHA_ACTION = 'woocommerce_checkout';

    private const FORM_ID = 'woocommerce-checkout';

    private ?PluginSettings $settings = null;

    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly EffectiveCaptchaProviderResolver $providerResolver,
        private readonly CaptchaProviderConfigurationFactory $configurationFactory,
        private readonly CaptchaServiceFactory $serviceFactory,
        private readonly CaptchaWidgetRenderer $widgetRenderer,
    ) {
    }

    public function render(): void
    {
        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return;
        }

        $this->widgetRenderer->enqueue(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );

        wp_enqueue_script(
            'wp-captcha-shield-classic-checkout-rehydrate',
            WP_CAPTCHA_SHIELD_URL . 'assets/js/classic-checkout-rehydrate.js',
            ['jquery'],
            WP_CAPTCHA_SHIELD_VERSION,
            true
        );

        $this->widgetRenderer->render(
            $effectiveProvider,
            $this->widgetContext(),
            $this->settings(),
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function validate(array $data, WP_Error $errors): void
    {
        unset($data);

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

        if (!$result->isSuccessful()) {
            $errors->add(
                'wp_captcha_shield_verification_failed',
                $this->visitorMessage($result),
            );
        }
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
        $formSetting = $settings->formSettings()[SupportedForms::WOOCOMMERCE_CHECKOUT]
            ?? FormCaptchaSetting::useDefault();

        return $this->providerResolver->resolve(
            $settings->globalSetting(),
            $formSetting,
        );
    }

    /**
     * WooCommerce validates the classic checkout nonce before this validation
     * hook runs.
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
