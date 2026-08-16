<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;
use WpCaptchaShield\WordPress\Settings\PluginSettings;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;

final class SettingsPage
{
    public const PAGE_SLUG = 'wp-captcha-shield';
    public const SAVE_ACTION = 'wp_captcha_shield_save_settings';
    public const NONCE_ACTION = 'wp_captcha_shield_save_settings';
    public const NONCE_NAME = 'wp_captcha_shield_nonce';

    private const TURNSTILE_PRIVACY_ADDENDUM_URL =
        'https://www.cloudflare.com/turnstile-privacy-policy/';

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

    public function enqueueAssets(string $hookSuffix): void
    {
        if ($hookSuffix !== 'settings_page_' . self::PAGE_SLUG) {
            return;
        }

        wp_enqueue_style(
            'wp-captcha-shield-admin-settings',
            WP_CAPTCHA_SHIELD_URL . 'assets/css/admin-settings.css',
            [],
            WP_CAPTCHA_SHIELD_VERSION,
        );

        wp_enqueue_script(
            'wp-captcha-shield-admin-settings',
            WP_CAPTCHA_SHIELD_URL . 'assets/js/admin-settings.js',
            [],
            WP_CAPTCHA_SHIELD_VERSION,
            true,
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
        <div class="wrap wp-captcha-shield-settings">
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
            <?php $this->renderTabs(); ?>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input
                    type="hidden"
                    name="action"
                    value="<?php echo esc_attr(self::SAVE_ACTION); ?>"
                >
                <?php wp_nonce_field(self::NONCE_ACTION, self::NONCE_NAME); ?>

                <section
                    id="wp-captcha-shield-tab-general"
                    class="wp-captcha-shield-tab-panel"
                    data-wpcs-tab-panel="general"
                    role="tabpanel"
                    aria-labelledby="wp-captcha-shield-tab-button-general"
                >
                    <?php $this->renderGeneralSection($settings); ?>
                    <?php submit_button(); ?>
                </section>

                <section
                    id="wp-captcha-shield-tab-turnstile"
                    class="wp-captcha-shield-tab-panel"
                    data-wpcs-tab-panel="turnstile"
                    role="tabpanel"
                    aria-labelledby="wp-captcha-shield-tab-button-turnstile"
                >
                    <?php $this->renderTurnstileSection($settings); ?>
                    <?php submit_button(); ?>
                </section>

                <section
                    id="wp-captcha-shield-tab-google"
                    class="wp-captcha-shield-tab-panel"
                    data-wpcs-tab-panel="google"
                    role="tabpanel"
                    aria-labelledby="wp-captcha-shield-tab-button-google"
                >
                    <?php $this->renderGoogleSection($settings); ?>
                    <?php submit_button(); ?>
                </section>

                <section
                    id="wp-captcha-shield-tab-hcaptcha"
                    class="wp-captcha-shield-tab-panel"
                    data-wpcs-tab-panel="hcaptcha"
                    role="tabpanel"
                    aria-labelledby="wp-captcha-shield-tab-button-hcaptcha"
                >
                    <?php $this->renderHCaptchaSection($settings); ?>
                    <?php submit_button(); ?>
                </section>

                <section
                    id="wp-captcha-shield-tab-status"
                    class="wp-captcha-shield-tab-panel"
                    data-wpcs-tab-panel="status"
                    role="tabpanel"
                    aria-labelledby="wp-captcha-shield-tab-button-status"
                >
                    <?php $this->renderStatusSection(); ?>
                </section>
            </form>
        </div>
        <?php
    }

    private function renderTabs(): void
    {
        $tabs = [
            'general' => __('General', 'wp-captcha-shield'),
            'turnstile' => __('Cloudflare Turnstile', 'wp-captcha-shield'),
            'google' => __('Google reCAPTCHA', 'wp-captcha-shield'),
            'hcaptcha' => __('hCaptcha', 'wp-captcha-shield'),
            'status' => __('Status', 'wp-captcha-shield'),
        ];
        ?>
        <nav
            class="nav-tab-wrapper wp-captcha-shield-tabs"
            role="tablist"
            aria-label="<?php echo esc_attr__('WP Captcha Shield settings', 'wp-captcha-shield'); ?>"
        >
            <?php foreach ($tabs as $tab => $label): ?>
                <button
                    type="button"
                    id="<?php echo esc_attr('wp-captcha-shield-tab-button-' . $tab); ?>"
                    class="nav-tab<?php echo $tab === 'general' ? ' nav-tab-active' : ''; ?>"
                    data-wpcs-tab="<?php echo esc_attr($tab); ?>"
                    role="tab"
                    aria-controls="<?php echo esc_attr('wp-captcha-shield-tab-' . $tab); ?>"
                    aria-selected="<?php echo esc_attr($tab === 'general' ? 'true' : 'false'); ?>"
                    tabindex="<?php echo esc_attr($tab === 'general' ? '0' : '-1'); ?>"
                >
                    <?php echo esc_html($label); ?>
                </button>
            <?php endforeach; ?>
        </nav>
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
                    <?php
                    $this->renderFieldLabel(
                        'wp-captcha-shield-global-provider',
                        __('Default provider', 'wp-captcha-shield'),
                    );
                    ?>
                </th>
                <td>
                    <select
                        id="wp-captcha-shield-global-provider"
                        name="wp_captcha_shield[global_provider]"
                    >
                        <?php $this->renderProviderOptions($selected, true); ?>
                    </select>
                    <?php
                    $this->renderFieldHelp(
                        'wp-captcha-shield-global-provider',
                        __('Default provider', 'wp-captcha-shield'),
                        __(
                            'Used by forms configured to “Use default”. Individual forms '
                            . 'can override this setting.',
                            'wp-captcha-shield',
                        ),
                    );
                    ?>
                </td>
            </tr>
        </table>

        <?php
        $this->renderFormSettingsGroup(
            $settings,
            __('WordPress forms', 'wp-captcha-shield'),
            false,
        );
        $this->renderFormSettingsGroup(
            $settings,
            __('WooCommerce forms', 'wp-captcha-shield'),
            true,
        );
    }

    private function renderFormSettingsGroup(
        PluginSettings $settings,
        string $heading,
        bool $wooCommerce,
    ): void {
        $forms = array_filter(
            $this->forms,
            static fn (string $label, string $formId): bool =>
                str_starts_with($formId, 'woocommerce_') === $wooCommerce,
            ARRAY_FILTER_USE_BOTH,
        );

        if ($forms === []) {
            return;
        }
        ?>
        <h3><?php echo esc_html($heading); ?></h3>
        <table class="form-table" role="presentation">
            <?php foreach ($forms as $formId => $label): ?>
                <?php
                $setting = $settings->formSettings()[$formId]
                    ?? FormCaptchaSetting::useDefault();
                $formValue = $this->formSettingValue($setting);
                $help = str_ends_with($formId, '_checkout')
                    ? __(
                        'Protects both Classic Checkout and Checkout Block. '
                        . '“Use default” inherits the Default provider setting.',
                        'wp-captcha-shield',
                    )
                    : __(
                        'Choose the CAPTCHA provider for this form. “Use default” '
                        . 'inherits the Default provider setting.',
                        'wp-captcha-shield',
                    );
                ?>
                <tr>
                    <th scope="row">
                        <?php
                        $this->renderFieldLabel(
                            'wp-captcha-shield-form-' . $formId,
                            $label,
                        );
                        ?>
                    </th>
                    <td>
                        <select
                            id="<?php echo esc_attr('wp-captcha-shield-form-' . $formId); ?>"
                            name="<?php echo esc_attr('wp_captcha_shield[forms][' . $formId . ']'); ?>"
                        >
                            <option
                                value="default"
                                <?php selected($formValue, 'default'); ?>
                            >
                                <?php echo esc_html__('Use default', 'wp-captcha-shield'); ?>
                            </option>
                            <?php $this->renderProviderOptions($formValue, true); ?>
                        </select>
                        <?php
                        $this->renderFieldHelp(
                            'wp-captcha-shield-form-' . $formId,
                            $label,
                            $help,
                        );
                        ?>
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
            $this->renderTextField(
                'turnstile-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][site_key]',
                $turnstile->siteKey(),
                help: __(
                    'Public site key supplied by Cloudflare. It is used in the browser to render Turnstile.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSecretField(
                'turnstile-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[turnstile][secret_key]',
                $turnstile->secretKey() !== '',
                __(
                    'Private key supplied by Cloudflare and used only on the server to verify CAPTCHA tokens.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSelectField(
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

    private function renderGoogleSection(PluginSettings $settings): void
    {
        $google = $settings->googleRecaptcha();
        ?>
        <h2><?php echo esc_html__('Google reCAPTCHA', 'wp-captcha-shield'); ?></h2>
        <table class="form-table" role="presentation">
            <?php
            $this->renderTextField(
                'google-project-id',
                __('Project ID', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][project_id]',
                $google->projectId(),
                help: __(
                    'Google Cloud project containing the reCAPTCHA Enterprise configuration.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSecretField(
                'google-api-key',
                __('API key', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][api_key]',
                $google->apiKey() !== '',
                __(
                    'Server-side Google Cloud API key used to create reCAPTCHA assessments.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderTextField(
                'google-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][site_key]',
                $google->siteKey(),
                help: __(
                    'reCAPTCHA Enterprise site key used by protected forms in the browser.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSelectField(
                'google-mode',
                __('Mode', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][mode]',
                $google->mode()->value,
                [
                    'score_based' => __('Score-based', 'wp-captcha-shield'),
                    'checkbox' => __('Checkbox', 'wp-captcha-shield'),
                    'invisible' => __('Invisible', 'wp-captcha-shield'),
                ],
                __(
                    'Determines how reCAPTCHA interacts with visitors. Score-based is recommended for most sites.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderTextField(
                'google-minimum-score',
                __('Minimum score', 'wp-captcha-shield'),
                'wp_captcha_shield[google_recaptcha][minimum_score]',
                (string) $google->minimumScore(),
                'number',
                '0',
                '1',
                '0.1',
                __(
                    'Minimum acceptable score for score-based verification. '
                    . 'Higher values are stricter. Use a value between 0 '
                    . 'and 1.',
                    'wp-captcha-shield',
                ),
            );
            ?>
        </table>
        <?php
    }

    private function renderHCaptchaSection(PluginSettings $settings): void
    {
        $hCaptcha = $settings->hCaptcha();
        ?>
        <h2><?php echo esc_html__('hCaptcha', 'wp-captcha-shield'); ?></h2>
        <table class="form-table" role="presentation">
            <?php
            $this->renderTextField(
                'hcaptcha-site-key',
                __('Site key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][site_key]',
                $hCaptcha->siteKey(),
                help: __(
                    'Public site key supplied by hCaptcha and used to display the CAPTCHA.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSecretField(
                'hcaptcha-secret-key',
                __('Secret key', 'wp-captcha-shield'),
                'wp_captcha_shield[hcaptcha][secret_key]',
                $hCaptcha->secretKey() !== '',
                __(
                    'Private server-side key used to verify submitted hCaptcha tokens.',
                    'wp-captcha-shield',
                ),
            );

            $this->renderSelectField(
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

    private function renderStatusSection(): void
    {
        $wordpressVersion = get_bloginfo('version');
        $wooCommerceVersion = defined('WC_VERSION') ? WC_VERSION : null;
        ?>
        <h2><?php echo esc_html__('Status and compatibility', 'wp-captcha-shield'); ?></h2>
        <p class="description">
            <?php echo esc_html__(
                'Compare the current environment with the minimum versions supported by WP Captcha Shield.',
                'wp-captcha-shield',
            ); ?>
        </p>
        <table class="widefat striped wp-captcha-shield-status-table">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__('Component', 'wp-captcha-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Minimum supported', 'wp-captcha-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Current', 'wp-captcha-shield'); ?></th>
                    <th scope="col"><?php echo esc_html__('Status', 'wp-captcha-shield'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                $this->renderStatusRow(
                    __('PHP', 'wp-captcha-shield'),
                    '8.1.0',
                    PHP_VERSION,
                    version_compare(
                        $this->normalizeVersion(PHP_VERSION),
                        '8.1.0',
                        '>=',
                    ),
                );
                $this->renderStatusRow(
                    __('WordPress', 'wp-captcha-shield'),
                    '6.7.0',
                    $wordpressVersion,
                    version_compare(
                        $this->normalizeVersion($wordpressVersion),
                        '6.7.0',
                        '>=',
                    ),
                );

                if ($wooCommerceVersion === null) {
                    $this->renderStatusRow(
                        __('WooCommerce', 'wp-captcha-shield'),
                        '10.1.0',
                        __('Not active', 'wp-captcha-shield'),
                        null,
                    );
                } else {
                    $this->renderStatusRow(
                        __('WooCommerce', 'wp-captcha-shield'),
                        '10.1.0',
                        $wooCommerceVersion,
                        version_compare(
                            $this->normalizeVersion($wooCommerceVersion),
                            '10.1.0',
                            '>=',
                        ),
                    );
                }
                ?>
            </tbody>
        </table>
        <p class="description">
            <?php echo esc_html__(
                'WooCommerce is optional. WordPress form protection remains available when WooCommerce is not active.',
                'wp-captcha-shield',
            ); ?>
        </p>

        <p class="description">
            <?php echo esc_html__(
                'WooCommerce versions below 10.1.0 are outside the supported compatibility range.',
                'wp-captcha-shield',
            ); ?>
        </p>
        <?php
    }

    /**
     * WordPress (and occasionally WooCommerce) reports "round" releases
     * without a trailing patch component — e.g. "6.7" instead of
     * "6.7.0" for the initial 6.7 release. PHP's version_compare()
     * treats a short version string as *older* than the same version
     * written with an explicit ".0" (version_compare('6.7', '6.7.0')
     * returns -1), which would otherwise flag a fully up-to-date site
     * as unsupported. Padding to three numeric components before
     * comparing avoids that false negative.
     */
    private function normalizeVersion(string $version): string
    {
        $parts = explode('.', $version, 3);

        return implode('.', array_pad($parts, 3, '0'));
    }

    private function renderStatusRow(
        string $component,
        string $minimumVersion,
        string $currentVersion,
        ?bool $compatible,
    ): void {
        $status = __('Optional', 'wp-captcha-shield');
        $statusClass = 'is-optional';

        if ($compatible === true) {
            $status = __('Compatible', 'wp-captcha-shield');
            $statusClass = 'is-compatible';
        } elseif ($compatible === false) {
            $status = __('Unsupported', 'wp-captcha-shield');
            $statusClass = 'is-unsupported';
        }
        ?>
        <tr>
            <th scope="row"><?php echo esc_html($component); ?></th>
            <td><?php echo esc_html($minimumVersion); ?></td>
            <td><?php echo esc_html($currentVersion); ?></td>
            <td>
                <span class="wp-captcha-shield-status <?php echo esc_attr($statusClass); ?>">
                    <?php echo esc_html($status); ?>
                </span>
            </td>
        </tr>
        <?php
    }

    private function renderConfigurationWarnings(PluginSettings $settings): void
    {
        $warnings = [];

        if (
            $settings->turnstile()->siteKey() === ''
            || $settings->turnstile()->secretKey() === ''
        ) {
            $warnings[] = __('Cloudflare Turnstile configuration is incomplete.', 'wp-captcha-shield');
        }

        if (
            $settings->googleRecaptcha()->projectId() === ''
            || $settings->googleRecaptcha()->apiKey() === ''
            || $settings->googleRecaptcha()->siteKey() === ''
        ) {
            $warnings[] = __('Google reCAPTCHA configuration is incomplete.', 'wp-captcha-shield');
        }

        if (
            $settings->hCaptcha()->siteKey() === ''
            || $settings->hCaptcha()->secretKey() === ''
        ) {
            $warnings[] = __('hCaptcha configuration is incomplete.', 'wp-captcha-shield');
        }

        foreach ($warnings as $warning) {
            ?>
            <div class="notice notice-warning">
                <p><?php echo esc_html($warning); ?></p>
            </div>
            <?php
        }
    }

    private function renderProviderOptions(string $selectedValue, bool $includeDisabled): void
    {
        if ($includeDisabled) {
            ?>
            <option value="disabled" <?php selected($selectedValue, 'disabled'); ?>>
                <?php echo esc_html__('Disabled', 'wp-captcha-shield'); ?>
            </option>
            <?php
        }

        $providers = [
            CaptchaProvider::CloudflareTurnstile->value => __('Cloudflare Turnstile', 'wp-captcha-shield'),
            CaptchaProvider::GoogleRecaptcha->value => __('Google reCAPTCHA', 'wp-captcha-shield'),
            CaptchaProvider::HCaptcha->value => __('hCaptcha', 'wp-captcha-shield'),
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
        ?string $help = null,
    ): void {
        ?>
        <tr>
            <th scope="row">
                <?php $this->renderFieldLabel($id, $label); ?>
            </th>
            <td>
                <input
                    class="regular-text"
                    type="<?php echo esc_attr($type); ?>"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    value="<?php echo esc_attr($value); ?>"
                    <?php if ($min !== null):
                        ?>min="<?php echo esc_attr($min); ?>"<?php
                    endif; ?>
                    <?php if ($max !== null):
                        ?>max="<?php echo esc_attr($max); ?>"<?php
                    endif; ?>
                    <?php if ($step !== null):
                        ?>step="<?php echo esc_attr($step); ?>"<?php
                    endif; ?>
                >
                <?php $this->renderFieldHelp($id, $label, $help); ?>
            </td>
        </tr>
        <?php
    }

    private function renderSecretField(
        string $id,
        string $label,
        string $name,
        bool $hasStoredValue,
        ?string $help = null,
    ): void {
        ?>
        <tr>
            <th scope="row">
                <?php $this->renderFieldLabel($id, $label); ?>
            </th>
            <td>
                <input
                    class="regular-text"
                    type="password"
                    id="<?php echo esc_attr($id); ?>"
                    name="<?php echo esc_attr($name); ?>"
                    value=""
                    autocomplete="new-password"
                >
                <?php $this->renderFieldHelp($id, $label, $help); ?>
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
        ?string $help = null,
    ): void {
        ?>
        <tr>
            <th scope="row">
                <?php $this->renderFieldLabel($id, $label); ?>
            </th>
            <td>
                <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>">
                    <?php foreach ($options as $value => $optionLabel): ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($selectedValue, $value); ?>>
                            <?php echo esc_html($optionLabel); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php $this->renderFieldHelp($id, $label, $help); ?>
            </td>
        </tr>
        <?php
    }

    private function renderFieldLabel(
        string $id,
        string $label,
    ): void {
        ?>
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($label); ?>
        </label>
        <?php
    }

    private function renderFieldHelp(
        string $id,
        string $label,
        ?string $help,
    ): void {
        if ($help === null) {
            return;
        }
        ?>
        <span class="wp-captcha-shield-help">
            <button
                type="button"
                class="wp-captcha-shield-help-button"
                aria-label="<?php echo esc_attr(sprintf(__('Help for %s', 'wp-captcha-shield'), $label)); ?>"
                aria-describedby="<?php echo esc_attr($id . '-help'); ?>"
            >?</button>
            <span
                id="<?php echo esc_attr($id . '-help'); ?>"
                class="wp-captcha-shield-tooltip"
                role="tooltip"
            >
                <?php echo esc_html($help); ?>
            </span>
        </span>
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