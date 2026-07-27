<?php

/**
 * Plugin Name:       WP Captcha Shield
 * Plugin URI:        https://vkuberan.in/wp-captcha-shield
 * Description:       WP Captcha Shield is a WordPress plugin for protecting selected WordPress and WooCommerce forms
 * Version:           0.1.0
 * Requires at least: 7.0
 * Requires PHP:      8.1
 * Author:            Velmurugan Kuberan
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-captcha-shield
 *
 * @package WpCaptchaShield
 */

declare(strict_types=1);

// Prevent direct file access.
if (!defined('ABSPATH')) {
    exit;
}

define('WP_CAPTCHA_SHIELD_VERSION', '0.1.0');
define('WP_CAPTCHA_SHIELD_FILE', __FILE__);
define('WP_CAPTCHA_SHIELD_PATH', plugin_dir_path(__FILE__));
define('WP_CAPTCHA_SHIELD_URL', plugin_dir_url(__FILE__));
