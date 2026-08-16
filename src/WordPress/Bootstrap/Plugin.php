<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap;

use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProviderResolver;
use WpCaptchaShield\Domain\Environment\EnvironmentCompatibility;
use WpCaptchaShield\WordPress\Admin\Sections\GeneralSettingsSection;
use WpCaptchaShield\WordPress\Admin\Sections\GoogleRecaptchaSettingsSection;
use WpCaptchaShield\WordPress\Admin\Sections\HCaptchaSettingsSection;
use WpCaptchaShield\WordPress\Admin\Sections\StatusSection;
use WpCaptchaShield\WordPress\Admin\Sections\TurnstileSettingsSection;
use WpCaptchaShield\WordPress\Admin\SettingsFieldRenderer;
use WpCaptchaShield\WordPress\Admin\SettingsInputMapper;
use WpCaptchaShield\WordPress\Admin\SettingsPage;
use WpCaptchaShield\WordPress\Admin\SettingsPageRegistrar;
use WpCaptchaShield\WordPress\Admin\SettingsPageView;
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
use WpCaptchaShield\WordPress\Forms\WordPressFormsBootstrap;
use WpCaptchaShield\WordPress\Http\WordPressHttpClient;
use WpCaptchaShield\WordPress\Settings\SettingsRepository;
use WpCaptchaShield\WordPress\Settings\WordPressSettingsRepository;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutIntegration;
use WpCaptchaShield\WordPress\WooCommerce\Checkout\WooCommerceBlockCheckoutRegistrar;
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

/**
 * Composition root for WP Captcha Shield.
 *
 * This is the single place where the domain, provider, and WordPress
 * layers are wired together into the full object graph, and where every
 * WordPress hook is ultimately registered. wp-captcha-shield.php only
 * calls Plugin::boot() — it does not construct anything itself.
 */
final class Plugin
{
    public function boot(): void
    {
        $settingsRepository = new WordPressSettingsRepository();

        $this->bootSettingsPage($settingsRepository);

        $providerResolver = new EffectiveCaptchaProviderResolver();
        $configurationFactory = new CaptchaProviderConfigurationFactory();
        $serviceFactory = new CaptchaServiceFactory(new WordPressHttpClient());
        $widgetRenderer = new CaptchaWidgetRenderer(
            new CaptchaWidgetResolver([
                new CloudflareTurnstileWidget(),
                new GoogleRecaptchaWidget(),
                new HCaptchaWidget(),
            ]),
        );

        $this->bootWordPressForms(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $this->bootWooCommerce(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );
    }

    private function bootSettingsPage(
        SettingsRepository $settingsRepository,
    ): void {
        $supportedForms = new SupportedForms();
        $fieldRenderer = new SettingsFieldRenderer();

        $settingsPageView = new SettingsPageView([
            new GeneralSettingsSection($fieldRenderer, $supportedForms->labels()),
            new TurnstileSettingsSection($fieldRenderer),
            new GoogleRecaptchaSettingsSection($fieldRenderer),
            new HCaptchaSettingsSection($fieldRenderer),
            new StatusSection(new EnvironmentCompatibility()),
        ]);

        $settingsPage = new SettingsPage(
            $settingsRepository,
            new SettingsInputMapper(),
            $settingsPageView,
            $supportedForms->labels(),
        );

        $settingsPageRegistrar = new SettingsPageRegistrar($settingsPage);
        $settingsPageRegistrar->registerHooks();
    }

    private function bootWordPressForms(
        SettingsRepository $settingsRepository,
        EffectiveCaptchaProviderResolver $providerResolver,
        CaptchaProviderConfigurationFactory $configurationFactory,
        CaptchaServiceFactory $serviceFactory,
        CaptchaWidgetRenderer $widgetRenderer,
    ): void {
        $loginFormIntegration = new LoginFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $registrationFormIntegration = new RegistrationFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $lostPasswordFormIntegration = new LostPasswordFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $commentFormIntegration = new CommentFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
            ['product'],
        );

        $wordPressFormsBootstrap = new WordPressFormsBootstrap(
            new LoginFormRegistrar($loginFormIntegration),
            new RegistrationFormRegistrar($registrationFormIntegration),
            new LostPasswordFormRegistrar($lostPasswordFormIntegration),
            new CommentFormRegistrar($commentFormIntegration),
        );
        $wordPressFormsBootstrap->registerHooks();
    }

    private function bootWooCommerce(
        SettingsRepository $settingsRepository,
        EffectiveCaptchaProviderResolver $providerResolver,
        CaptchaProviderConfigurationFactory $configurationFactory,
        CaptchaServiceFactory $serviceFactory,
        CaptchaWidgetRenderer $widgetRenderer,
    ): void {
        $wooCommerceLoginFormIntegration = new WooCommerceLoginFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceRegistrationFormIntegration = new WooCommerceRegistrationFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceLostPasswordFormIntegration = new WooCommerceLostPasswordFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceProductReviewFormIntegration = new WooCommerceProductReviewFormIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceClassicCheckoutIntegration = new WooCommerceClassicCheckoutIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceBlockCheckoutIntegration = new WooCommerceBlockCheckoutIntegration(
            $settingsRepository,
            $providerResolver,
            $configurationFactory,
            $serviceFactory,
            $widgetRenderer,
        );

        $wooCommerceBootstrap = new WooCommerceBootstrap(
            new WooCommerceAvailability(),
            new WooCommerceLoginFormRegistrar(
                $wooCommerceLoginFormIntegration,
            ),
            new WooCommerceRegistrationFormRegistrar(
                $wooCommerceRegistrationFormIntegration,
            ),
            new WooCommerceLostPasswordFormRegistrar(
                $wooCommerceLostPasswordFormIntegration,
            ),
            new WooCommerceProductReviewFormRegistrar(
                $wooCommerceProductReviewFormIntegration,
            ),
            new WooCommerceClassicCheckoutRegistrar(
                $wooCommerceClassicCheckoutIntegration,
            ),
            new WooCommerceBlockCheckoutRegistrar(
                $wooCommerceBlockCheckoutIntegration,
            ),
        );
        $wooCommerceBootstrap->registerHooks();
    }
}
