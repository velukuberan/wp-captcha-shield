<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class GoogleRecaptchaWidget implements CaptchaWidget
{
    public const TOKEN_FIELD = 'wp_captcha_shield_google_token';

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::GoogleRecaptcha;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context);

        $siteKey = rawurlencode(
            $settings->googleRecaptcha()->siteKey(),
        );

        wp_enqueue_script(
            'wp-captcha-shield-google-recaptcha',
            'https://www.google.com/recaptcha/enterprise.js?render='
            . $siteKey,
            [],
            null,
            true,
        );
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $google = $settings->googleRecaptcha();

        if ($google->mode() === GoogleRecaptchaMode::Checkbox) {
            printf(
                '<div class="g-recaptcha" '
                . 'data-sitekey="%s" '
                . 'data-action="%s"></div>',
                esc_attr($google->siteKey()),
                esc_attr($context->action()),
            );

            return;
        }

        printf(
            '<input type="hidden" name="%s" id="%s" value="">',
            esc_attr(self::TOKEN_FIELD),
            esc_attr(self::TOKEN_FIELD),
        );
        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.getElementById(
                    '<?php echo esc_js($context->formId()); ?>'
                );
                var token = document.getElementById(
                    '<?php echo esc_js(self::TOKEN_FIELD); ?>'
                );

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
                            {
                                action:
                                    '<?php echo esc_js($context->action()); ?>'
                            }
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

    public function tokenFieldName(PluginSettings $settings): string
    {
        return $settings->googleRecaptcha()->mode()
            === GoogleRecaptchaMode::Checkbox
            ? 'g-recaptcha-response'
            : self::TOKEN_FIELD;
    }
}
