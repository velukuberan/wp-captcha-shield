<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Admin;

/**
 * Renders the form-field widgets shared by every settings tab: text,
 * secret, and select inputs, each with a label and an optional inline
 * help tooltip.
 */
final class SettingsFieldRenderer
{
    public function renderTextField(
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

    public function renderSecretField(
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
    public function renderSelectField(
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

    public function renderFieldLabel(
        string $id,
        string $label,
    ): void {
        ?>
        <label for="<?php echo esc_attr($id); ?>">
            <?php echo esc_html($label); ?>
        </label>
        <?php
    }

    public function renderFieldHelp(
        string $id,
        string $label,
        ?string $help,
    ): void {
        if ($help === null) {
            return;
        }

        $helpLabel = sprintf(
            /* translators: %s: Settings field label. */
            __('Help for %s', 'wp-captcha-shield'),
            $label,
        );
        ?>
        <span class="wp-captcha-shield-help">
            <button
                type="button"
                class="wp-captcha-shield-help-button"
                aria-label="<?php echo esc_attr($helpLabel); ?>"
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
}
