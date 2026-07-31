<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Login;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class CaptchaWidgetRenderer
{
    public const GOOGLE_TOKEN_FIELD = 'wp_captcha_shield_google_token';

    public function enqueue(
        EffectiveCaptchaProvider $effectiveProvider,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return;
        }

        wp_enqueue_style(
            'wp-captcha-shield-login',
            WP_CAPTCHA_SHIELD_URL . 'assets/css/login.css',
            [],
            WP_CAPTCHA_SHIELD_VERSION,
        );

        if ($provider === CaptchaProvider::CloudflareTurnstile) {
            wp_enqueue_script(
                'wp-captcha-shield-turnstile',
                'https://challenges.cloudflare.com/turnstile/v0/api.js',
                [],
                null,
                true,
            );
        }

        if ($provider === CaptchaProvider::GoogleRecaptcha) {
            $siteKey = rawurlencode($settings->googleRecaptcha()->siteKey());
            wp_enqueue_script(
                'wp-captcha-shield-google-recaptcha',
                'https://www.google.com/recaptcha/enterprise.js?render=' . $siteKey,
                [],
                null,
                true,
            );
        }

        if ($provider === CaptchaProvider::HCaptcha) {
            wp_enqueue_script(
                'wp-captcha-shield-hcaptcha',
                'https://js.hcaptcha.com/1/api.js',
                [],
                null,
                true,
            );
        }
    }

    public function render(
        EffectiveCaptchaProvider $effectiveProvider,
        PluginSettings $settings,
    ): void {
        $provider = $effectiveProvider->provider();

        if ($provider === CaptchaProvider::CloudflareTurnstile) {
            $turnstile = $settings->turnstile();

            if ($turnstile->mode() === CloudflareTurnstileMode::Invisible) {
                printf(
                    '<div class="cf-turnstile" '
                    . 'data-sitekey="%s" '
                    . 'data-action="wordpress_login"></div>',
                    esc_attr($turnstile->siteKey()),
                );

                return;
            }

            if ($turnstile->mode() === CloudflareTurnstileMode::NonInteractive) {
                printf(
                    '<div class="wp-captcha-shield-widget">'
                    . '<div class="cf-turnstile" '
                    . 'data-sitekey="%s" '
                    . 'data-size="flexible" '
                    . 'data-appearance="always" '
                    . 'data-action="wordpress_login"></div>'
                    . '</div>',
                    esc_attr($turnstile->siteKey()),
                );

                return;
            }

            printf(
                '<div class="wp-captcha-shield-widget">'
                . '<div class="cf-turnstile" '
                . 'data-sitekey="%s" '
                . 'data-size="flexible" '
                . 'data-action="wordpress_login"></div>'
                . '</div>',
                esc_attr($turnstile->siteKey()),
            );

            return;
        }

        if ($provider === CaptchaProvider::HCaptcha) {
            printf(
                '<div class="h-captcha" data-sitekey="%s"></div>',
                esc_attr($settings->hCaptcha()->siteKey()),
            );
            return;
        }

        if ($provider === CaptchaProvider::GoogleRecaptcha) {
            $google = $settings->googleRecaptcha();

            if ($google->mode() === GoogleRecaptchaMode::Checkbox) {
                printf(
                    '<div class="g-recaptcha" data-sitekey="%s" data-action="wordpress_login"></div>',
                    esc_attr($google->siteKey()),
                );
                return;
            }

            printf(
                '<input type="hidden" name="%s" id="%s" value="">',
                esc_attr(self::GOOGLE_TOKEN_FIELD),
                esc_attr(self::GOOGLE_TOKEN_FIELD),
            );
            ?>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    var form = document.getElementById('loginform');
                    var token = document.getElementById('<?php echo esc_js(self::GOOGLE_TOKEN_FIELD); ?>');

                    if (!form || !token || typeof grecaptcha === 'undefined') {
                        return;
                    }

                    form.addEventListener('submit', function (event) {
                        if (token.value !== '') {
                            return;
                        }

                        event.preventDefault();
                        grecaptcha.enterprise.ready(function () {
                            grecaptcha.enterprise.execute(
                                '<?php echo esc_js($google->siteKey()); ?>',
                                {action: 'wordpress_login'}
                            ).then(function (value) {
                                token.value = value;
                                form.submit();
                            });
                        });
                    });
                });
            </script>
            <?php
        }
    }
}
