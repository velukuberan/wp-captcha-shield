<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\WordPress\Settings\GoogleRecaptchaSettings;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class GoogleRecaptchaWidget implements CaptchaWidget
{
    public const TOKEN_FIELD = 'wp_captcha_shield_google_token';

    private const SCORE_TOKEN_CLASS =
        'wp-captcha-shield-google-score-token';

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::GoogleRecaptcha;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($context);

        $google = $settings->googleRecaptcha();
        $siteKey = rawurlencode($google->siteKey());

        wp_enqueue_script(
            'wp-captcha-shield-google-recaptcha',
            'https://www.google.com/recaptcha/enterprise.js?render='
            . $siteKey,
            [],
            null,
            true,
        );

        if ($google->mode() !== GoogleRecaptchaMode::ScoreBased) {
            return;
        }

        wp_enqueue_script(
            'wp-captcha-shield-google-recaptcha-score-based',
            WP_CAPTCHA_SHIELD_URL
            . 'assets/js/google-recaptcha-score-based.js',
            ['wp-captcha-shield-google-recaptcha'],
            WP_CAPTCHA_SHIELD_VERSION,
            true,
        );
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $google = $settings->googleRecaptcha();

        match ($google->mode()) {
            GoogleRecaptchaMode::ScoreBased =>
                $this->renderScoreBased($context, $google),
            GoogleRecaptchaMode::Checkbox =>
                $this->renderCheckbox($context, $google),
            GoogleRecaptchaMode::Invisible =>
                $this->renderInvisible($context, $google),
        };
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        return $settings->googleRecaptcha()->mode()
            === GoogleRecaptchaMode::Checkbox
            ? 'g-recaptcha-response'
            : self::TOKEN_FIELD;
    }

    private function renderScoreBased(
        CaptchaWidgetContext $context,
        GoogleRecaptchaSettings $settings,
    ): void {
        printf(
            '<input type="hidden" name="%s" id="%s" class="%s" '
            . 'data-form-id="%s" data-site-key="%s" '
            . 'data-action="%s" value="">',
            esc_attr(self::TOKEN_FIELD),
            esc_attr($this->scoreTokenId($context)),
            esc_attr(self::SCORE_TOKEN_CLASS),
            esc_attr($context->formId()),
            esc_attr($settings->siteKey()),
            esc_attr($context->action()),
        );
    }

    private function renderCheckbox(
        CaptchaWidgetContext $context,
        GoogleRecaptchaSettings $settings,
    ): void {
        printf(
            '<div class="g-recaptcha" '
            . 'data-sitekey="%s" '
            . 'data-action="%s"></div>',
            esc_attr($settings->siteKey()),
            esc_attr($context->action()),
        );
    }

    private function renderInvisible(
        CaptchaWidgetContext $context,
        GoogleRecaptchaSettings $settings,
    ): void {
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
                            '<?php echo esc_js($settings->siteKey()); ?>',
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

    private function scoreTokenId(CaptchaWidgetContext $context): string
    {
        return self::TOKEN_FIELD
            . '-'
            . sanitize_html_class($context->formId());
    }
}
