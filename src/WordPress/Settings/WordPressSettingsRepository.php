<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;

final class WordPressSettingsRepository implements SettingsRepository
{
    public const OPTION_NAME = 'wp_captcha_shield_settings';

    public function load(): PluginSettings
    {
        $stored = get_option(self::OPTION_NAME, []);

        if (!is_array($stored)) {
            return PluginSettings::defaults();
        }

        return new PluginSettings(
            $this->globalSetting($stored['global_provider'] ?? null),
            $this->formSettings($stored['forms'] ?? null),
            $this->turnstileSettings($stored['turnstile'] ?? null),
            $this->googleRecaptchaSettings($stored['google_recaptcha'] ?? null),
            $this->hCaptchaSettings($stored['hcaptcha'] ?? null),
        );
    }

    public function save(PluginSettings $settings): void
    {
        update_option(
            self::OPTION_NAME,
            [
                'global_provider' => $this->globalProviderValue(
                    $settings->globalSetting(),
                ),
                'forms' => $this->formSettingValues($settings->formSettings()),
                'turnstile' => [
                    'site_key' => $settings->turnstile()->siteKey(),
                    'secret_key' => $settings->turnstile()->secretKey(),
                    'mode' => $settings->turnstile()->mode()->value,
                ],
                'google_recaptcha' => [
                    'project_id' => $settings->googleRecaptcha()->projectId(),
                    'api_key' => $settings->googleRecaptcha()->apiKey(),
                    'site_key' => $settings->googleRecaptcha()->siteKey(),
                    'minimum_score' => $settings->googleRecaptcha()->minimumScore(),
                    'mode' => $settings->googleRecaptcha()->mode()->value,
                ],
                'hcaptcha' => [
                    'site_key' => $settings->hCaptcha()->siteKey(),
                    'secret_key' => $settings->hCaptcha()->secretKey(),
                    'mode' => $settings->hCaptcha()->mode()->value,
                ],
            ],
            false,
        );
    }

    private function globalSetting(mixed $value): GlobalCaptchaSetting
    {
        $provider = is_string($value)
            ? CaptchaProvider::tryFrom($value)
            : null;

        return $provider === null
            ? GlobalCaptchaSetting::disabled()
            : GlobalCaptchaSetting::provider($provider);
    }

    /**
     * @return array<string, FormCaptchaSetting>
     */
    private function formSettings(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $settings = [];

        foreach ($value as $formId => $formValue) {
            if (!is_string($formId) || !is_string($formValue)) {
                continue;
            }

            $settings[$formId] = match ($formValue) {
                'default' => FormCaptchaSetting::useDefault(),
                'disabled' => FormCaptchaSetting::disabled(),
                default => $this->providerFormSetting($formValue),
            };
        }

        return $settings;
    }

    private function providerFormSetting(string $value): FormCaptchaSetting
    {
        $provider = CaptchaProvider::tryFrom($value);

        return $provider === null
            ? FormCaptchaSetting::useDefault()
            : FormCaptchaSetting::provider($provider);
    }

    private function turnstileSettings(mixed $value): TurnstileSettings
    {
        $settings = is_array($value) ? $value : [];

        return new TurnstileSettings(
            $this->stringValue($settings['site_key'] ?? null),
            $this->stringValue($settings['secret_key'] ?? null),
            $this->turnstileMode($settings['mode'] ?? null),
        );
    }

    private function googleRecaptchaSettings(mixed $value): GoogleRecaptchaSettings
    {
        $settings = is_array($value) ? $value : [];
        $minimumScore = $settings['minimum_score'] ?? null;

        return new GoogleRecaptchaSettings(
            $this->stringValue($settings['project_id'] ?? null),
            $this->stringValue($settings['api_key'] ?? null),
            $this->stringValue($settings['site_key'] ?? null),
            $this->minimumScore($minimumScore),
            $this->googleRecaptchaMode($settings['mode'] ?? null),
        );
    }

    private function hCaptchaSettings(mixed $value): HCaptchaSettings
    {
        $settings = is_array($value) ? $value : [];

        return new HCaptchaSettings(
            $this->stringValue($settings['site_key'] ?? null),
            $this->stringValue($settings['secret_key'] ?? null),
            $this->hCaptchaMode($settings['mode'] ?? null),
        );
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? $value : '';
    }

    private function minimumScore(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.5;
        }

        $score = (float) $value;

        return $score >= 0.0 && $score <= 1.0 ? $score : 0.5;
    }

    private function turnstileMode(mixed $value): CloudflareTurnstileMode
    {
        return is_string($value)
            ? CloudflareTurnstileMode::tryFrom($value)
                ?? CloudflareTurnstileMode::Managed
            : CloudflareTurnstileMode::Managed;
    }

    private function googleRecaptchaMode(mixed $value): GoogleRecaptchaMode
    {
        return is_string($value)
            ? GoogleRecaptchaMode::tryFrom($value)
                ?? GoogleRecaptchaMode::ScoreBased
            : GoogleRecaptchaMode::ScoreBased;
    }

    private function hCaptchaMode(mixed $value): HCaptchaDisplayMode
    {
        return is_string($value)
            ? HCaptchaDisplayMode::tryFrom($value)
                ?? HCaptchaDisplayMode::Checkbox
            : HCaptchaDisplayMode::Checkbox;
    }

    private function globalProviderValue(
        GlobalCaptchaSetting $setting,
    ): ?string {
        return $setting->selectedProvider()?->value;
    }

    /**
     * @param array<string, FormCaptchaSetting> $settings
     * @return array<string, string>
     */
    private function formSettingValues(array $settings): array
    {
        $values = [];

        foreach ($settings as $formId => $setting) {
            $values[$formId] = match (true) {
                $setting->usesDefault() => 'default',
                $setting->isDisabled() => 'disabled',
                default => $setting->selectedProvider()->value,
            };
        }

        return $values;
    }
}
