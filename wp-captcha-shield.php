<?php

/**
 * Plugin Name:       WP Captcha Shield
 * Plugin URI:        https://github.com/velukuberan/wp-captcha-shield
 * Description:       Protects selected WordPress and WooCommerce forms using configurable CAPTCHA providers.
 * Version:           0.1.0-beta1
 * Requires at least: 6.7.0
 * Requires PHP:      8.1
 * Author:            Velmurugan Kuberan
 * Author URI:        https://vkuberan.in
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-captcha-shield
 *
 * @package WpCaptchaShield
 */

declare(strict_types=1);

use WpCaptchaShield\WordPress\Bootstrap\Plugin;

if (!defined('ABSPATH')) {
    exit;
}

if (!function_exists('get_file_data')) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
$wpCaptchaShieldPluginData = get_file_data(__FILE__, ['Version' => 'Version']);
$wpCaptchaShieldVersion = $wpCaptchaShieldPluginData['Version'] ?? '';

if ($wpCaptchaShieldVersion === '') {
    $wpCaptchaShieldVersion = '0.0.0';
}

define('WP_CAPTCHA_SHIELD_VERSION', $wpCaptchaShieldVersion);
unset($wpCaptchaShieldPluginData, $wpCaptchaShieldVersion);
define('WP_CAPTCHA_SHIELD_FILE', __FILE__);
define('WP_CAPTCHA_SHIELD_PATH', plugin_dir_path(__FILE__));
define('WP_CAPTCHA_SHIELD_URL', plugin_dir_url(__FILE__));

require_once WP_CAPTCHA_SHIELD_PATH . 'vendor/autoload.php';

(new Plugin())->boot();
