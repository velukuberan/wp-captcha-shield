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
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetRenderer;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetResolver;
use WpCaptchaShield\WordPress\Forms\Captcha\CloudflareTurnstileWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\GoogleRecaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\HCaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormIntegration;
use WpCaptchaShield\WordPress\Forms\Comments\CommentFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormIntegration;
use WpCaptchaShield\WordPress\Forms\Login\LoginFormRegistrar;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormIntegration;
use WpCaptchaShield\WordPress\Forms\LostPassword\LostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\Forms\Registration\RegistrationFormIntegration;
use WpCaptchaShield\WordPress\Forms\Registration\RegistrationFormRegistrar;
use WpCaptchaShield\WordPress\Forms\SupportedForms;
use WpCaptchaShield\WordPress\Http\WordPressHttpClient;
use WpCaptchaShield\WordPress\Settings\WordPressSettingsRepository;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceClassicCheckoutIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceClassicCheckoutRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Login\WooCommerceLoginFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\LostPassword\WooCommerceLostPasswordFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\LostPassword\WooCommerceLostPasswordFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\ProductReviews\WooCommerceProductReviewFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\ProductReviews\WooCommerceProductReviewFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Registration\WooCommerceRegistrationFormRegistrar;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceAvailability;
use WpCaptchaShield\WordPress\WooCommerce\WooCommerceBootstrap;

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

$captchaWidgetResolver = new CaptchaWidgetResolver([
    new CloudflareTurnstileWidget(),
    new GoogleRecaptchaWidget(),
    new HCaptchaWidget(),
]);
$providerResolver = new EffectiveCaptchaProviderResolver();
$configurationFactory = new CaptchaProviderConfigurationFactory();
$serviceFactory = new CaptchaServiceFactory(new WordPressHttpClient());
$widgetRenderer = new CaptchaWidgetRenderer($captchaWidgetResolver);

$loginFormIntegration = new LoginFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$loginFormRegistrar = new LoginFormRegistrar($loginFormIntegration);
$loginFormRegistrar->registerHooks();

$registrationFormIntegration = new RegistrationFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$registrationFormRegistrar = new RegistrationFormRegistrar(
    $registrationFormIntegration,
);
$registrationFormRegistrar->registerHooks();

$lostPasswordFormIntegration = new LostPasswordFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$lostPasswordFormRegistrar = new LostPasswordFormRegistrar(
    $lostPasswordFormIntegration,
);
$lostPasswordFormRegistrar->registerHooks();

$commentFormIntegration = new CommentFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
    ['product'],
);
$commentFormRegistrar = new CommentFormRegistrar($commentFormIntegration);
$commentFormRegistrar->registerHooks();

$wooCommerceLoginFormIntegration = new WooCommerceLoginFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$wooCommerceLoginFormRegistrar = new WooCommerceLoginFormRegistrar(
    $wooCommerceLoginFormIntegration,
);

$wooCommerceRegistrationFormIntegration = new WooCommerceRegistrationFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$wooCommerceRegistrationFormRegistrar = new WooCommerceRegistrationFormRegistrar(
    $wooCommerceRegistrationFormIntegration,
);

$wooCommerceLostPasswordFormIntegration = new WooCommerceLostPasswordFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$wooCommerceLostPasswordFormRegistrar = new WooCommerceLostPasswordFormRegistrar(
    $wooCommerceLostPasswordFormIntegration,
);

$wooCommerceProductReviewFormIntegration = new WooCommerceProductReviewFormIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$wooCommerceProductReviewFormRegistrar = new WooCommerceProductReviewFormRegistrar(
    $wooCommerceProductReviewFormIntegration,
);

$wooCommerceClassicCheckoutIntegration = new WooCommerceClassicCheckoutIntegration(
    $settingsRepository,
    $providerResolver,
    $configurationFactory,
    $serviceFactory,
    $widgetRenderer,
);
$wooCommerceClassicCheckoutRegistrar = new WooCommerceClassicCheckoutRegistrar(
    $wooCommerceClassicCheckoutIntegration,
);

$wooCommerceBootstrap = new WooCommerceBootstrap(
    new WooCommerceAvailability(),
    $wooCommerceLoginFormRegistrar,
    $wooCommerceRegistrationFormRegistrar,
    $wooCommerceLostPasswordFormRegistrar,
    $wooCommerceProductReviewFormRegistrar,
    $wooCommerceClassicCheckoutRegistrar,
);
$wooCommerceBootstrap->registerHooks();
