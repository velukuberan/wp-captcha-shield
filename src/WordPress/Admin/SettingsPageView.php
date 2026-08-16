<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

use WpCaptchaShield\WordPress\Admin\Sections\SettingsTabSection;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

/**
 * Renders the settings page shell: the page title, save notice,
 * configuration warnings, tab navigation, and the save form that wraps
 * every registered tab's panel.
 */
final class SettingsPageView
{
    /**
     * @param list<SettingsTabSection> $sections
     */
    public function __construct(
        private readonly array $sections = [],
    ) {
    }

    public function render(PluginSettings $settings): void
    {
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
                    value="<?php echo esc_attr(SettingsPage::SAVE_ACTION); ?>"
                >
                <?php wp_nonce_field(SettingsPage::NONCE_ACTION, SettingsPage::NONCE_NAME); ?>

                <?php foreach ($this->sections as $section): ?>
                    <section
                        id="<?php echo esc_attr('wp-captcha-shield-tab-' . $section->slug()); ?>"
                        class="wp-captcha-shield-tab-panel"
                        data-wpcs-tab-panel="<?php echo esc_attr($section->slug()); ?>"
                        role="tabpanel"
                        aria-labelledby="<?php echo esc_attr('wp-captcha-shield-tab-button-' . $section->slug()); ?>"
                    >
                        <?php $section->render($settings); ?>
                        <?php if ($section->showsSubmitButton()): ?>
                            <?php submit_button(); ?>
                        <?php endif; ?>
                    </section>
                <?php endforeach; ?>
            </form>
        </div>
        <?php
    }

    private function renderTabs(): void
    {
        ?>
        <nav
            class="nav-tab-wrapper wp-captcha-shield-tabs"
            role="tablist"
            aria-label="<?php echo esc_attr__('WP Captcha Shield settings', 'wp-captcha-shield'); ?>"
        >
            <?php foreach ($this->sections as $index => $section): ?>
                <?php $isActive = $index === 0; ?>
                <button
                    type="button"
                    id="<?php echo esc_attr('wp-captcha-shield-tab-button-' . $section->slug()); ?>"
                    class="nav-tab<?php echo $isActive ? ' nav-tab-active' : ''; ?>"
                    data-wpcs-tab="<?php echo esc_attr($section->slug()); ?>"
                    role="tab"
                    aria-controls="<?php echo esc_attr('wp-captcha-shield-tab-' . $section->slug()); ?>"
                    aria-selected="<?php echo esc_attr($isActive ? 'true' : 'false'); ?>"
                    tabindex="<?php echo esc_attr($isActive ? '0' : '-1'); ?>"
                >
                    <?php echo esc_html($section->label()); ?>
                </button>
            <?php endforeach; ?>
        </nav>
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
}
