<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaProviderConfigurationFactory
{
    public function create(
        PluginSettings $settings,
    ): CaptchaProviderConfiguration {
        return new CaptchaProviderConfiguration(
            new CloudflareTurnstileConfiguration(
                $settings->turnstile()->secretKey(),
            ),
            new GoogleRecaptchaConfiguration(
                $settings->googleRecaptcha()->projectId(),
                $settings->googleRecaptcha()->apiKey(),
                $settings->googleRecaptcha()->siteKey(),
                $settings->googleRecaptcha()->minimumScore(),
                $settings->googleRecaptcha()->mode(),
            ),
            new HCaptchaConfiguration(
                $settings->hCaptcha()->secretKey(),
                $settings->hCaptcha()->siteKey(),
            ),
        );
    }
}
