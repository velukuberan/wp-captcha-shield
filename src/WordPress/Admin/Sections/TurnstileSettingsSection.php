<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class TurnstileSettingsSection implements SettingsTabSection
{
    private const TURNSTILE_PRIVACY_ADDENDUM_URL =
        'https://www.cloudflare.com/turnstile-privacy-policy/';

    public function __construct(
        private readonly SettingsFieldRenderer $fields,
    ) {
    }

    public function slug(): string
    {
        return 'turnstile';
    }

    public function label(): string
    {
        return __('Cloudflare Turnstile', 'wp-captcha-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
    {
        $turnstile = $settings->turnstile();
        ?>
        <h2><?php echo esc_html__('Cloudflare Turnstile', 'wp-captcha-shield'); ?></h2>

        <p class="description">
            <?php echo esc_html__(
                'The widget mode is configured in your Cloudflare Turnstile dashboard.',
                'wp-captcha-shield',
            ); ?>
        </p>

        <p class="description">
            <?php echo esc_html__(
                'Select the same mode here as the mode configured for this '
                . 'site key. Changing this setting does not change the '
                . 'widget mode in Cloudflare.',
                'wp-captcha-shield',
            ); ?>
        </p>

        <table class="form-table" role="presentation">
            <?php
            $this->fields->renderTextField(
                'turnstile-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][site_key]',
                $turnstile->siteKey(),
                help: __(
                    'Public site key supplied by Cloudflare. It is used in the browser to render Turnstile.',
                    'wp-captcha-shield',
                ),
            );

            $this->fields->renderSecretField(
                'turnstile-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][secret_key]',
                $turnstile->secretKey() !== '',
                __(
                    'Private key supplied by Cloudflare and used only on the server to verify CAPTCHA tokens.',
                    'wp-captcha-shield',
                ),
            );

            $this->fields->renderSelectField(
                'turnstile-mode',
                __('Mode', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][mode]',
                $turnstile->mode()->value,
                [
                    'managed' => __('Managed', 'wp-captcha-shield'),
                    'non_interactive' => __('Non-Interactive', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
                __(
                    'Must match the widget mode configured for this site key in '
                    . 'Cloudflare. Managed is recommended for most sites.',
                    'wp-captcha-shield',
                ),
            );
            ?>

            <?php if ($turnstile->mode() === CloudflareTurnstileMode::Invisible): ?>
                <tr>
                    <th scope="row"></th>
                    <td>
                        <div class="notice notice-warning inline">
                            <p>
                                <?php
                                printf(
                                    wp_kses(
                                        __(
                                            'Cloudflare requires websites using Invisible Turnstile to '
                                            . 'reference the <a href="%s" target="_blank" '
                                            . 'rel="noopener noreferrer">Turnstile Privacy '
                                            . 'Addendum</a> in their privacy policy.',
                                            'wp-captcha-shield',
                                        ),
                                        [
                                            'a' => [
                                                'href' => true,
                                                'target' => true,
                                                'rel' => true,
                                            ],
                                        ],
                                    ),
                                    esc_url(self::TURNSTILE_PRIVACY_ADDENDUM_URL),
                                );
                                ?>
                            </p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
        </table>
        <?php
    }
}
