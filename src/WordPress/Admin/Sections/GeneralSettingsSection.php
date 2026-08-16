<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin\Sections;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\FormCaptchaSetting;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class GeneralSettingsSection implements SettingsTabSection
{
    /**
     * @param array<string, string> $forms Form ID => translated label.
     */
    public function __construct(
        private readonly SettingsFieldRenderer $fields,
        private readonly array $forms = [],
    ) {
    }

    public function slug(): string
    {
        return 'general';
    }

    public function label(): string
    {
        return __('General', 'wp-captcha-shield');
    }

    public function showsSubmitButton(): bool
    {
        return true;
    }

    public function render(PluginSettings $settings): void
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
                    $this->fields->renderFieldLabel(
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
                    $this->fields->renderFieldHelp(
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
                        $this->fields->renderFieldLabel(
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
                        $this->fields->renderFieldHelp(
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
