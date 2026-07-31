<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;

final class PluginSettings
{
    /**
     * @param array<string, FormCaptchaSetting> $formSettings
     */
    public function __construct(
        private readonly GlobalCaptchaSetting $globalSetting,
        private readonly array $formSettings,
        private readonly TurnstileSettings $turnstile,
        private readonly GoogleRecaptchaSettings $googleRecaptcha,
        private readonly HCaptchaSettings $hCaptcha,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            GlobalCaptchaSetting::disabled(),
            [],
            TurnstileSettings::defaults(),
            GoogleRecaptchaSettings::defaults(),
            HCaptchaSettings::defaults(),
        );
    }

    public function globalSetting(): GlobalCaptchaSetting
    {
        return $this->globalSetting;
    }

    /**
     * @return array<string, FormCaptchaSetting>
     */
    public function formSettings(): array
    {
        return $this->formSettings;
    }

    public function turnstile(): TurnstileSettings
    {
        return $this->turnstile;
    }

    public function googleRecaptcha(): GoogleRecaptchaSettings
    {
        return $this->googleRecaptcha;
    }

    public function hCaptcha(): HCaptchaSettings
    {
        return $this->hCaptcha;
    }
}
