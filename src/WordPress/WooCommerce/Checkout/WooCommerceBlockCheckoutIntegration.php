<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce\Checkout;

use WP_Error;
use WP_REST_Request;
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

final class WooCommerceBlockCheckoutIntegration
{
    private const CAPTCHA_ACTION = 'woocommerce_checkout';

    private const FORM_ID = 'woocommerce-block-checkout';

    private const EXTENSION_NAMESPACE = 'wp-captcha-shield';

    private const EXTENSION_TOKEN_KEY = 'token';

    private ?PluginSettings $settings = null;

    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly EffectiveCaptchaProviderResolver $providerResolver,
        private readonly CaptchaProviderConfigurationFactory $configurationFactory,
        private readonly CaptchaServiceFactory $serviceFactory,
        private readonly CaptchaWidgetRenderer $widgetRenderer,
    ) {
    }

    public function render(string $blockContent): string
    {
        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return $blockContent;
        }

        $context = $this->widgetContext();

        $this->widgetRenderer->enqueue(
            $effectiveProvider,
            $context,
            $this->settings(),
        );

        wp_enqueue_script(
            'wp-captcha-shield-woocommerce-block-checkout',
            WP_CAPTCHA_SHIELD_URL . 'assets/js/woocommerce-block-checkout.js',
            ['wp-data', 'wc-blocks-data-store'],
            WP_CAPTCHA_SHIELD_VERSION,
            true,
        );

        ob_start();
        $this->widgetRenderer->render(
            $effectiveProvider,
            $context,
            $this->settings(),
        );
        $widgetMarkup = ob_get_clean();

        if ($widgetMarkup === false) {
            return $blockContent;
        }

        $tokenField = $this->widgetRenderer->tokenFieldName(
            $effectiveProvider,
            $this->settings(),
        );

        if ($tokenField === '') {
            return $blockContent;
        }

        return sprintf(
            '<div class="wp-captcha-shield-block-checkout" '
            . 'data-wp-captcha-shield-block-checkout '
            . 'data-token-field="%s" '
            . 'data-error-message="%s">%s</div>',
            esc_attr($tokenField),
            esc_attr(__(
                'CAPTCHA verification failed. Please try again.',
                'wp-captcha-shield',
            )),
            $widgetMarkup,
        ) . $blockContent;
    }

    public function validate(
        mixed $result,
        mixed $server,
        WP_REST_Request $request,
    ): mixed {
        unset($server);

        if ($result !== null) {
            return $result;
        }

        if (
            $request->get_method() !== 'POST'
            || !$this->isCheckoutRoute($request->get_route())
        ) {
            return $result;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return $result;
        }

        $verificationResult = $this->serviceFactory
            ->create(
                $this->configurationFactory->create($this->settings()),
            )
            ->verify(
                $effectiveProvider,
                new CaptchaVerificationRequest(
                    $this->submittedToken($request->get_param('extensions')),
                    $this->serverValue('REMOTE_ADDR'),
                    $this->serverValue('HTTP_USER_AGENT'),
                    self::CAPTCHA_ACTION,
                ),
            );

        if ($verificationResult->isSuccessful()) {
            return $result;
        }

        return new WP_Error(
            'wp_captcha_shield_verification_failed',
            $this->visitorMessage($verificationResult),
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
        $formSetting = $settings->formSettings()[SupportedForms::WOOCOMMERCE_CHECKOUT]
            ?? FormCaptchaSetting::useDefault();

        return $this->providerResolver->resolve(
            $settings->globalSetting(),
            $formSetting,
        );
    }

    private function isCheckoutRoute(string $route): bool
    {
        return preg_match(
            '#^/wc/store(?:/v\d+)?/checkout/?$#',
            $route,
        ) === 1;
    }

    private function submittedToken(mixed $extensions): string
    {
        if (!is_array($extensions)) {
            return '';
        }

        $extension = $extensions[self::EXTENSION_NAMESPACE] ?? null;

        if (!is_array($extension)) {
            return '';
        }

        $token = $extension[self::EXTENSION_TOKEN_KEY] ?? null;

        if (!is_string($token)) {
            return '';
        }

        return sanitize_text_field($token);
    }

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
