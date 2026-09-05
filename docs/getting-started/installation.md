# Installation

WP Captcha Shield can be installed from the WordPress admin area using the
plugin ZIP package.

## Requirements

Minimum supported versions:

- PHP 8.1 or newer
- WordPress 6.7.0 or newer
- WooCommerce 10.1.0 or newer when WooCommerce protection is used

WooCommerce is optional. WordPress form protection remains available when
WooCommerce is not installed or active.

## Install the plugin

1. In WordPress, go to **Plugins → Add New**.
2. Select **Upload Plugin**.
3. Choose the WP Captcha Shield ZIP package.
4. Select **Install Now**.
5. Activate **WP Captcha Shield**.

After activation, go to:

    Settings → WP Captcha Shield

## Configure CAPTCHA

1. Configure the credentials for at least one CAPTCHA provider.
2. On the **General** tab, select the provider you want to use as the global default.
3. Optionally override or disable CAPTCHA for individual WordPress or WooCommerce forms.
4. Save the settings.
5. Test the protected forms on your site.

## Next steps

Continue with the provider setup guide for the provider you want to use:

- [Cloudflare Turnstile](../providers/cloudflare-turnstile.md)
- [Google reCAPTCHA](../providers/google-recaptcha.md)
- [hCaptcha](../providers/hcaptcha.md)

After configuring a provider, see the [Form setup](../forms/index.md) guide for
the global default and per-form configuration model.
