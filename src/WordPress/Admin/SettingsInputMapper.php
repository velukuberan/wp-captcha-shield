<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class SettingsInputMapper
{
    /**
     * @param array<string, mixed> $input
     * @param list<string> $allowedFormIds
     */
    public function map(
        array $input,
        PluginSettings $current,
        array $allowedFormIds,
    ): PluginSettings {
        return new PluginSettings(
            $this->globalSetting($input['global_provider'] ?? null),
            $this->formSettings($input['forms'] ?? null, $allowedFormIds),
            $this->turnstileSettings($input['turnstile'] ?? null, $current),
            $this->googleRecaptchaSettings(
                $input['google_recaptcha'] ?? null,
                $current,
            ),
            $this->hCaptchaSettings($input['hcaptcha'] ?? null, $current),
        );
    }

    private function globalSetting(mixed $value): GlobalCaptchaSetting
    {
        if ($value === 'disabled') {
            return GlobalCaptchaSetting::disabled();
        }

        $provider = is_string($value)
            ? CaptchaProvider::tryFrom($value)
            : null;

        return $provider === null
            ? GlobalCaptchaSetting::disabled()
            : GlobalCaptchaSetting::provider($provider);
    }

    /**
     * @param list<string> $allowedFormIds
     * @return array<string, FormCaptchaSetting>
     */
    private function formSettings(
        mixed $value,
        array $allowedFormIds,
    ): array {
        if (!is_array($value)) {
            return [];
        }

        $settings = [];

        foreach ($allowedFormIds as $formId) {
            $formValue = $value[$formId] ?? 'default';

            if (!is_string($formValue)) {
                $formValue = 'default';
            }

            $settings[$formId] = match ($formValue) {
                'disabled' => FormCaptchaSetting::disabled(),
                'default' => FormCaptchaSetting::useDefault(),
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

    private function turnstileSettings(
        mixed $value,
        PluginSettings $current,
    ): TurnstileSettings {
        $input = is_array($value) ? $value : [];

        return new TurnstileSettings(
            $this->stringValue($input['site_key'] ?? null),
            $this->preservedSecret(
                $input['secret_key'] ?? null,
                $current->turnstile()->secretKey(),
            ),
            $this->enumValue(
                $input['mode'] ?? null,
                CloudflareTurnstileMode::class,
                CloudflareTurnstileMode::Managed,
            ),
        );
    }

    private function googleRecaptchaSettings(
        mixed $value,
        PluginSettings $current,
    ): GoogleRecaptchaSettings {
        $input = is_array($value) ? $value : [];

        return new GoogleRecaptchaSettings(
            $this->stringValue($input['project_id'] ?? null),
            $this->preservedSecret(
                $input['api_key'] ?? null,
                $current->googleRecaptcha()->apiKey(),
            ),
            $this->stringValue($input['site_key'] ?? null),
            $this->minimumScore($input['minimum_score'] ?? null),
            $this->enumValue(
                $input['mode'] ?? null,
                GoogleRecaptchaMode::class,
                GoogleRecaptchaMode::ScoreBased,
            ),
        );
    }

    private function hCaptchaSettings(
        mixed $value,
        PluginSettings $current,
    ): HCaptchaSettings {
        $input = is_array($value) ? $value : [];

        return new HCaptchaSettings(
            $this->stringValue($input['site_key'] ?? null),
            $this->preservedSecret(
                $input['secret_key'] ?? null,
                $current->hCaptcha()->secretKey(),
            ),
            $this->enumValue(
                $input['mode'] ?? null,
                HCaptchaDisplayMode::class,
                HCaptchaDisplayMode::Checkbox,
            ),
        );
    }

    private function stringValue(mixed $value): string
    {
        return is_string($value) ? trim($value) : '';
    }

    private function preservedSecret(mixed $value, string $current): string
    {
        $newValue = $this->stringValue($value);

        return $newValue === '' ? $current : $newValue;
    }

    private function minimumScore(mixed $value): float
    {
        if (!is_numeric($value)) {
            return 0.5;
        }

        $score = (float) $value;

        return $score >= 0.0 && $score <= 1.0 ? $score : 0.5;
    }

    /**
     * @template T of \BackedEnum
     * @param class-string<T> $enum
     * @param T $default
     * @return T
     */
    private function enumValue(
        mixed $value,
        string $enum,
        \BackedEnum $default,
    ): \BackedEnum {
        if (!is_string($value)) {
            return $default;
        }

        return $enum::tryFrom($value) ?? $default;
    }
}
