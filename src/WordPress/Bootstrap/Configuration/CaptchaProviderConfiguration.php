<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

final class CaptchaProviderConfiguration
{
    public function __construct(
        private CloudflareTurnstileConfiguration $turnstile,
        private GoogleRecaptchaConfiguration $googleRecaptcha,
        private HCaptchaConfiguration $hCaptcha,
    ) {
    }

    public function turnstile(): CloudflareTurnstileConfiguration
    {
        return $this->turnstile;
    }

    public function googleRecaptcha(): GoogleRecaptchaConfiguration
    {
        return $this->googleRecaptcha;
    }

    public function hCaptcha(): HCaptchaConfiguration
    {
        return $this->hCaptcha;
    }
}
