<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class HCaptchaSettingsSection implements SettingsTabSection
{
    public function __construct(
        private readonly SettingsFieldRenderer $fields,
    ) {
    }

    public function slug(): string
    {
        return 'hcaptcha';
    }

    public function label(): string
    {
        return __('hCaptcha', 'wp-captcha-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
    {
        $hCaptcha = $settings->hCaptcha();
        ?>
        <h2><?php echo esc_html__('hCaptcha', 'wp-captcha-shield'); ?></h2>
        <table class="form-table" role="presentation">
            <?php
            $this->fields->renderTextField(
                'hcaptcha-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][site_key]',
                $hCaptcha->siteKey(),
                help: __(
                    'Public site key supplied by hCaptcha and used to display the CAPTCHA.',
                    'wp-captcha-shield',
                ),
            );

            $this->fields->renderSecretField(
                'hcaptcha-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][secret_key]',
                $hCaptcha->secretKey() !== '',
                __(
                    'Private server-side key used to verify submitted hCaptcha tokens.',
                    'wp-captcha-shield',
                ),
            );

            $this->fields->renderSelectField(
                'hcaptcha-mode',
                __('Display mode', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][mode]',
                $hCaptcha->mode()->value,
                [
                    'checkbox' => __('Checkbox', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
                __(
                    'Controls how hCaptcha is presented to visitors. Checkbox is the default option.',
                    'wp-captcha-shield',
                ),
            );
            ?>
        </table>
        <?php
    }
}
