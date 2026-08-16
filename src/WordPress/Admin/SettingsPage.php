<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

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
        private readonly SettingsPageView $view,
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

        $this->view->render($settings);
    }
}
