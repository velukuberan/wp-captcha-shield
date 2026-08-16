<?php

declare(strict_types=1);

/**
 * Uninstall handler for WP Captcha Shield.
 *
 * WordPress runs this file when the plugin is deleted from the Plugins
 * screen (not on deactivation), and only after confirming the delete
 * request — WP_UNINSTALL_PLUGIN is only defined in that context, so the
 * guard below also keeps this file inert if it's ever requested directly.
 *
 * This plugin does not declare multisite network-activation support, so
 * uninstalling it removes its settings for the current site only. If
 * network-activation is added later, this should loop over get_sites()
 * and call delete_option() in each site's context instead.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

delete_option(
    WpCaptchaShield\WordPress\Settings\WordPressSettingsRepository::OPTION_NAME,
);