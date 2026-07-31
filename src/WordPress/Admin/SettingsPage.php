<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class SettingsPage
{
    public const PAGE_SLUG = 'wp-captcha-shield';
    public const SAVE_ACTION = 'wp_captcha_shield_save_settings';
    public const NONCE_ACTION = 'wp_captcha_shield_save_settings';
    public const NONCE_NAME = 'wp_captcha_shield_nonce';

    /**
     * @param array<string, string> $forms Form ID => translated label.
     */
    public function __construct(
        private readonly SettingsRepository $repository,
        private readonly SettingsInputMapper $inputMapper,
        private readonly array $forms = [],
    ) {
    }

    public function register(): void
    {
        add_options_page(
            __('WP Captcha Shield', 'wp-captcha-shield'),
            __('WP Captcha Shield', 'wp-captcha-shield'),
            'manage_options',
            self::PAGE_SLUG,
            [$this, 'render'],
        );
    }

    public function save(): void
    {
        if (!current_user_can('manage_options')) {
            wp_die(
                esc_html__(
                    'You are not allowed to manage these settings.',
                    'wp-captcha-shield',
                ),
            );
        }

        check_admin_referer(self::NONCE_ACTION, self::NONCE_NAME);

        $input = [];

        if (
            isset($_POST['wp_captcha_shield'])
            && is_array($_POST['wp_captcha_shield'])
        ) {
            $input = map_deep(
                wp_unslash($_POST['wp_captcha_shield']),
                'sanitize_text_field',
            );
        }

        $current = $this->repository->load();
        $settings = $this->inputMapper->map(
            $input,
            $current,
            array_keys($this->forms),
        );

        $this->repository->save($settings);

        wp_safe_redirect(
            add_query_arg(
                [
                    'page' => self::PAGE_SLUG,
                    'settings-updated' => 'true',
                ],
                admin_url('options-general.php'),
            ),
        );
        exit;
    }

    public function render(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $settings = $this->repository->load();
        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html__('WP Captcha Shield', 'wp-captcha-shield'); ?>
            </h1>

            <?php
            /*
             * This query parameter only controls a success notice after the
             * nonce-protected save request redirects back to this page.
             */
            // phpcs:disable WordPress.Security.NonceVerification.Recommended
            $settingsUpdated = isset($_GET['settings-updated'])
                && sanitize_text_field(
                    wp_unslash($_GET['settings-updated']),
                ) === 'true';
            // phpcs:enable WordPress.Security.NonceVerification.Recommended
            ?>

            <?php if ($settingsUpdated): ?>
                <div class="notice notice-success is-dismissible">
                    <p>
                        <?php echo esc_html__(
                            'Settings saved.',
                            'wp-captcha-shield',
                        ); ?>
                    </p>
                </div>
            <?php endif; ?>

            <?php $this->renderConfigurationWarnings($settings); ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr(self::SAVE_ACTION); ?>">
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <?php $this->renderGeneralSection($settings); ?>
                <?php $this->renderTurnstileSection($settings); ?>
                <?php $this->renderGoogleSection($settings); ?>
                <?php $this->renderHCaptchaSection($settings); ?>

                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    private function renderGeneralSection(PluginSettings $settings): void
    {
        $selected = $settings->globalSetting()->selectedProvider()->value
            ?? 'disabled';
        ?>
        <h2>
            <?php echo esc_html__('General settings', 'wp-captcha-shield'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="wp-captcha-shield-global-provider">
                        <?php echo esc_html__('Default provider', 'wp-captcha-shield'); ?>
                    </label>
                </th>
                <td>
                    <select id="wp-captcha-shield-global-provider" name="wp_captcha_shield[global_provider]">
                        <?php $this->renderProviderOptions($selected, true); ?>
                    </select>
                </td>
            </tr>

            <?php foreach ($this->forms as $formId => $label): ?>
                <?php
                $setting = $settings->formSettings()[$formId]
                    ?? FormCaptchaSetting::useDefault();
                $formValue = $this->formSettingValue($setting);
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr('wp-captcha-shield-form-' . $formId); ?>">
                            <?php echo esc_html($label); ?>
                        </label>
                    </th>
                    <td>
                        <select id="<?php echo esc_attr('wp-captcha-shield-form-' . $formId); ?>"
                            name="<?php echo esc_attr('wp_captcha_shield[forms][' . $formId . ']'); ?>">
                            <option value="default" <?php selected($formValue, 'default'); ?>>
                                <?php echo esc_html__('Use default', 'wp-captcha-shield'); ?>
                            </option>
                            <?php $this->renderProviderOptions($formValue, true); ?>
                        </select>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php
    }

    private function renderTurnstileSection(PluginSettings $settings): void
    {
        $turnstile = $settings->turnstile();
        ?>
        <h2>
            <?php echo esc_html__('Cloudflare Turnstile', 'wp-captcha-shield'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <?php $this->renderTextField(
                'turnstile-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][site_key]',
                $turnstile->siteKey(),
            ); ?>
            <?php $this->renderSecretField(
                'turnstile-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][secret_key]',
                $turnstile->secretKey() !== '',
            ); ?>
            <?php $this->renderSelectField(
                'turnstile-mode',
                __('Mode', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][mode]',
                $turnstile->mode()->value,
                [
                    'managed' => __('Managed', 'wp-captcha-shield'),
                    'non_interactive' => __('Non-Interactive', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
            ); ?>
        </table>
        <?php
    }

    private function renderGoogleSection(PluginSettings $settings): void
    {
        $google = $settings->googleRecaptcha();
        ?>
        <h2>
            <?php echo esc_html__('Google reCAPTCHA', 'wp-captcha-shield'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <?php $this->renderTextField(
                'google-project-id',
                __('Project ID', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][project_id]',
                $google->projectId(),
            ); ?>
            <?php $this->renderSecretField(
                'google-api-key',
                __('API key', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][api_key]',
                $google->apiKey() !== '',
            ); ?>
            <?php $this->renderTextField(
                'google-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][site_key]',
                $google->siteKey(),
            ); ?>
            <?php $this->renderSelectField(
                'google-mode',
                __('Mode', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][mode]',
                $google->mode()->value,
                [
                    'score_based' => __('Score-based', 'wp-captcha-shield'),
                    'checkbox' => __('Checkbox', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
            ); ?>
            <?php $this->renderTextField(
                'google-minimum-score',
                __('Minimum score', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][minimum_score]',
                (string) $google->minimumScore(),
                'number',
                '0',
                '1',
                '0.1',
            ); ?>
        </table>
        <?php
    }

    private function renderHCaptchaSection(PluginSettings $settings): void
    {
        $hCaptcha = $settings->hCaptcha();
        ?>
        <h2>
            <?php echo esc_html__('hCaptcha', 'wp-captcha-shield'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <?php $this->renderTextField(
                'hcaptcha-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][site_key]',
                $hCaptcha->siteKey(),
            ); ?>
            <?php $this->renderSecretField(
                'hcaptcha-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][secret_key]',
                $hCaptcha->secretKey() !== '',
            ); ?>
            <?php $this->renderSelectField(
                'hcaptcha-mode',
                __('Display mode', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][mode]',
                $hCaptcha->mode()->value,
                [
                    'checkbox' => __('Checkbox', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
            ); ?>
        </table>
        <?php
    }

    private function renderConfigurationWarnings(PluginSettings $settings): void
    {
        $warnings = [];

        if (
            $settings->turnstile()->siteKey() === ''
            || $settings->turnstile()->secretKey() === ''
        ) {
            $warnings[] = __(
                'Cloudflare Turnstile configuration is incomplete.',
                'wp-captcha-shield',
            );
        }

        if (
            $settings->googleRecaptcha()->projectId() === ''
            || $settings->googleRecaptcha()->apiKey() === ''
            || $settings->googleRecaptcha()->siteKey() === ''
        ) {
            $warnings[] = __(
                'Google reCAPTCHA configuration is incomplete.',
                'wp-captcha-shield',
            );
        }

        if (
            $settings->hCaptcha()->siteKey() === ''
            || $settings->hCaptcha()->secretKey() === ''
        ) {
            $warnings[] = __(
                'hCaptcha configuration is incomplete.',
                'wp-captcha-shield',
            );
        }

        foreach ($warnings as $warning) {
            ?>
            <div class="notice notice-warning">
                <p>
                    <?php echo esc_html($warning); ?>
                </p>
            </div>
            <?php
        }
    }

    private function renderProviderOptions(
        string $selectedValue,
        bool $includeDisabled,
    ): void {
        if ($includeDisabled) {
            ?>
            <option value="disabled" <?php selected($selectedValue, 'disabled'); ?>>
                <?php echo esc_html__('Disabled', 'wp-captcha-shield'); ?>
            </option>
            <?php
        }

        $providers = [
            CaptchaProvider::CloudflareTurnstile->value => __(
                'Cloudflare Turnstile',
                'wp-captcha-shield',
            ),
            CaptchaProvider::GoogleRecaptcha->value => __(
                'Google reCAPTCHA',
                'wp-captcha-shield',
            ),
            CaptchaProvider::HCaptcha->value => __(
                'hCaptcha',
                'wp-captcha-shield',
            ),
        ];

        foreach ($providers as $value => $label) {
            ?>
            <option value="<?php echo esc_attr($value); ?>" <?php selected($selectedValue, $value); ?>>
                <?php echo esc_html($label); ?>
            </option>
            <?php
        }
    }

    private function renderTextField(
        string $id,
        string $label,
        string $name,
        string $value,
        string $type = 'text',
        ?string $min = null,
        ?string $max = null,
        ?string $step = null,
    ): void {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($label); ?>
                </label></th>
            <td>
            <input
                class="regular-text"
                type="<?php echo esc_attr($type); ?>"
                id="<?php echo esc_attr($id); ?>"
                name="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_attr($value); ?>"
                    <?php if ($min !== null) : ?>
                    min="<?php echo esc_attr($min); ?>"
                    <?php endif; ?>
                    <?php if ($max !== null) : ?>
                    max="<?php echo esc_attr($max); ?>"
                    <?php endif; ?>
                    <?php if ($step !== null) : ?>
                    step="<?php echo esc_attr($step); ?>"
                    <?php endif; ?>
            >
            </td>
        </tr>
        <?php
    }

    private function renderSecretField(
        string $id,
        string $label,
        string $name,
        bool $hasStoredValue,
    ): void {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($label); ?>
                </label></th>
            <td>
                <input class="regular-text" type="password" id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr($name); ?>" value="" autocomplete="new-password">
                <?php if ($hasStoredValue): ?>
                    <p class="description">
                        <?php echo esc_html__(
                            'A value is stored. Leave blank to keep it unchanged.',
                            'wp-captcha-shield',
                        ); ?>
                    </p>
                <?php endif; ?>
            </td>
        </tr>
        <?php
    }

    /**
     * @param array<string, string> $options
     */
    private function renderSelectField(
        string $id,
        string $label,
        string $name,
        string $selectedValue,
        array $options,
    ): void {
        ?>
        <tr>
            <th scope="row"><label for="<?php echo esc_attr($id); ?>">
                    <?php echo esc_html($label); ?>
                </label></th>
            <td>
                <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>">
                    <?php foreach ($options as $value => $optionLabel): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($selectedValue, $value); ?>>
                            <?php echo esc_html($optionLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
        </tr>
        <?php
    }

    private function formSettingValue(FormCaptchaSetting $setting): string
    {
        if ($setting->usesDefault()) {
            return 'default';
        }

        if ($setting->isDisabled()) {
            return 'disabled';
        }

        return $setting->selectedProvider()->value;
    }
}