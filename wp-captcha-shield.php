<?php

/**
 * Plugin Name:       WP Captcha Shield
 * Plugin URI:        https://github.com/velukuberan/wp-captcha-shield
 * Description:       Protects selected WordPress and WooCommerce forms using configurable CAPTCHA providers.
 * Version:           0.1.0
 * Requires at least: 6.9
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

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Admin\SettingsPageRegistrar;
use WpCaptchaShield\WordPress\Bootstrap\CaptchaServiceFactory;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfigurationFactory;
use WpCaptchaShield\WordPress\Forms\Login\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormIntegration;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormRegistrar;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Http\WordPressHttpClient;
use WpCaptchaShield\WordPress\Settings\WordPressSettingsRepository;

if (!defined('ABSPATH')) {
    exit;
}

define('WP_CAPTCHA_SHIELD_VERSION', '0.1.0');
define('WP_CAPTCHA_SHIELD_FILE', __FILE__);
define('WP_CAPTCHA_SHIELD_PATH', plugin_dir_path(__FILE__));
define('WP_CAPTCHA_SHIELD_URL', plugin_dir_url(__FILE__));

require_once WP_CAPTCHA_SHIELD_PATH . 'vendor/autoload.php';

$settingsRepository = new WordPressSettingsRepository();
$supportedForms = new SupportedForms();

$settingsPage = new SettingsPage(
    $settingsRepository,
    new SettingsInputMapper(),
    $supportedForms->labels(),
);

$settingsPageRegistrar = new SettingsPageRegistrar($settingsPage);
$settingsPageRegistrar->registerHooks();

$loginFormIntegration = new LoginFormIntegration(
    $settingsRepository,
    new EffectiveCaptchaProviderResolver(),
    new CaptchaProviderConfigurationFactory(),
    new CaptchaServiceFactory(new WordPressHttpClient()),
    new CaptchaWidgetRenderer(),
);

$loginFormRegistrar = new LoginFormRegistrar($loginFormIntegration);
$loginFormRegistrar->registerHooks();
