<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Login;

use WP_Error;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationResult;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class LoginFormIntegration
{
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
        $this->widgetRenderer->enqueue(
            $this->effectiveProvider(),
            $this->settings(),
        );
    }

    public function render(): void
    {
        $this->widgetRenderer->render(
            $this->effectiveProvider(),
            $this->settings(),
        );
    }

    public function authenticate(
        mixed $user,
        string $username,
        string $password,
    ): mixed {
        unset($username, $password);

        if ($user instanceof WP_Error || !$this->isLoginSubmission()) {
            return $user;
        }

        $effectiveProvider = $this->effectiveProvider();

        if ($effectiveProvider->isDisabled()) {
            return $user;
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
                    'wordpress_login',
                ),
            );

        if ($result->isSuccessful()) {
            return $user;
        }

        return new WP_Error(
            'wp_captcha_shield_verification_failed',
            $this->visitorMessage($result),
        );
    }

    private function settings(): PluginSettings
    {
        return $this->settings ??= $this->repository->load();
    }

    private function effectiveProvider(): EffectiveCaptchaProvider
    {
        $settings = $this->settings();
        $formSetting = $settings->formSettings()[SupportedForms::WORDPRESS_LOGIN]
            ?? FormCaptchaSetting::useDefault();

        return $this->providerResolver->resolve(
            $settings->globalSetting(),
            $formSetting,
        );
    }

    /**
     * WordPress core does not include a nonce in the login form.
     * These values are read only during the native login POST request.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Missing

    private function isLoginSubmission(): bool
    {
        return $this->serverValue('REQUEST_METHOD') === 'POST'
            && isset($_POST['log'], $_POST['pwd']);
    }

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
