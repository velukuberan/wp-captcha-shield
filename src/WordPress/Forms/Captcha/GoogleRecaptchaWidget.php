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

    private const INVISIBLE_WIDGET_CLASS =
        'wp-captcha-shield-google-invisible-widget';

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

        wp_enqueue_script(
            'wp-captcha-shield-google-recaptcha',
            $this->scriptUrl($google),
            [],
            null,
            true,
        );

        $asset = match ($google->mode()) {
            GoogleRecaptchaMode::ScoreBased => [
                'wp-captcha-shield-google-recaptcha-score-based',
                'assets/js/google-recaptcha-score-based.js',
            ],
            GoogleRecaptchaMode::Invisible => [
                'wp-captcha-shield-google-recaptcha-invisible',
                'assets/js/google-recaptcha-invisible.js',
            ],
            GoogleRecaptchaMode::Checkbox => null,
        };

        if ($asset === null) {
            return;
        }

        wp_enqueue_script(
            $asset[0],
            WP_CAPTCHA_SHIELD_URL . $asset[1],
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
        $this->renderExecutableToken(
            $context,
            $settings,
            self::SCORE_TOKEN_CLASS,
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
        $tokenId = $this->tokenId($context);

        printf(
            '<input type="hidden" name="%s" id="%s" value="">'
            . '<div id="%s" class="%s" '
            . 'data-form-id="%s" data-token-id="%s" '
            . 'data-site-key="%s" data-action="%s"></div>',
            esc_attr(self::TOKEN_FIELD),
            esc_attr($tokenId),
            esc_attr($this->invisibleWidgetId($context)),
            esc_attr(self::INVISIBLE_WIDGET_CLASS),
            esc_attr($context->formId()),
            esc_attr($tokenId),
            esc_attr($settings->siteKey()),
            esc_attr($context->action()),
        );
    }

    private function renderExecutableToken(
        CaptchaWidgetContext $context,
        GoogleRecaptchaSettings $settings,
        string $className,
    ): void {
        printf(
            '<input type="hidden" name="%s" id="%s" class="%s" '
            . 'data-form-id="%s" data-site-key="%s" '
            . 'data-action="%s" value="">',
            esc_attr(self::TOKEN_FIELD),
            esc_attr($this->tokenId($context)),
            esc_attr($className),
            esc_attr($context->formId()),
            esc_attr($settings->siteKey()),
            esc_attr($context->action()),
        );
    }

    private function tokenId(CaptchaWidgetContext $context): string
    {
        return self::TOKEN_FIELD
            . '-'
            . sanitize_html_class($context->formId());
    }

    private function invisibleWidgetId(
        CaptchaWidgetContext $context,
    ): string {
        return 'wp-captcha-shield-google-invisible-widget-'
            . sanitize_html_class($context->formId());
    }

    private function scriptUrl(
        GoogleRecaptchaSettings $settings,
    ): string {
        return match ($settings->mode()) {
            GoogleRecaptchaMode::ScoreBased =>
                'https://www.google.com/recaptcha/enterprise.js?render='
                . rawurlencode($settings->siteKey()),
            GoogleRecaptchaMode::Checkbox =>
                'https://www.google.com/recaptcha/enterprise.js',
            GoogleRecaptchaMode::Invisible =>
                'https://www.google.com/recaptcha/enterprise.js?render=explicit',
        };
    }
}
