<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Admin;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\GlobalCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\HCaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\TurnstileSettings;

final class SettingsInputMapperTest extends TestCase
{
    public function testItMapsValidSettingsAndPreservesBlankSecrets(): void
    {
        $current = new PluginSettings(
            GlobalCaptchaSetting::disabled(),
            [],
            new TurnstileSettings(
                'old-turnstile-site',
                'stored-turnstile-secret',
                CloudflareTurnstileMode::Managed,
            ),
            new GoogleRecaptchaSettings(
                'old-project',
                'stored-google-api-key',
                'old-google-site',
                0.5,
                GoogleRecaptchaMode::ScoreBased,
            ),
            new HCaptchaSettings(
                'old-hcaptcha-site',
                'stored-hcaptcha-secret',
                HCaptchaDisplayMode::Checkbox,
            ),
        );

        $settings = (new SettingsInputMapper())->map(
            [
                'global_provider' => CaptchaProvider::CloudflareTurnstile->value,
                'forms' => [
                    'login' => CaptchaProvider::HCaptcha->value,
                    'unknown' => CaptchaProvider::GoogleRecaptcha->value,
                ],
                'turnstile' => [
                    'site_key' => 'new-turnstile-site',
                    'secret_key' => '',
                    'mode' => 'invisible',
                ],
                'google_recaptcha' => [
                    'project_id' => 'new-project',
                    'api_key' => '',
                    'site_key' => 'new-google-site',
                    'minimum_score' => '0.7',
                    'mode' => 'checkbox',
                ],
                'hcaptcha' => [
                    'site_key' => 'new-hcaptcha-site',
                    'secret_key' => '',
                    'mode' => 'invisible',
                ],
            ],
            $current,
            ['login'],
        );

        self::assertSame(
            CaptchaProvider::CloudflareTurnstile,
            $settings->globalSetting()->selectedProvider(),
        );
        self::assertSame(
            CaptchaProvider::HCaptcha,
            $settings->formSettings()['login']->selectedProvider(),
        );
        self::assertArrayNotHasKey('unknown', $settings->formSettings());

        self::assertSame(
            'stored-turnstile-secret',
            $settings->turnstile()->secretKey(),
        );
        self::assertSame(
            'stored-google-api-key',
            $settings->googleRecaptcha()->apiKey(),
        );
        self::assertSame(
            'stored-hcaptcha-secret',
            $settings->hCaptcha()->secretKey(),
        );
        self::assertSame(0.7, $settings->googleRecaptcha()->minimumScore());
    }

    public function testItFallsBackSafelyForMalformedValues(): void
    {
        $settings = (new SettingsInputMapper())->map(
            [
                'global_provider' => 'unsupported',
                'forms' => ['login' => 'unsupported'],
                'turnstile' => ['mode' => 'unsupported'],
                'google_recaptcha' => [
                    'minimum_score' => '4.2',
                    'mode' => 'unsupported',
                ],
                'hcaptcha' => ['mode' => 'unsupported'],
            ],
            PluginSettings::defaults(),
            ['login'],
        );

        self::assertTrue($settings->globalSetting()->isDisabled());
        self::assertTrue($settings->formSettings()['login']->usesDefault());
        self::assertSame(
            CloudflareTurnstileMode::Managed,
            $settings->turnstile()->mode(),
        );
        self::assertSame(
            GoogleRecaptchaMode::ScoreBased,
            $settings->googleRecaptcha()->mode(),
        );
        self::assertSame(
            HCaptchaDisplayMode::Checkbox,
            $settings->hCaptcha()->mode(),
        );
        self::assertSame(0.5, $settings->googleRecaptcha()->minimumScore());
    }
}
