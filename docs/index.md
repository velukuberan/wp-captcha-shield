# WP Captcha Shield documentation

WP Captcha Shield protects selected WordPress and WooCommerce forms with
configurable CAPTCHA providers.

<div class="grid cards" markdown>

-   :material-cloud-check:{ .lg .middle } **Cloudflare Turnstile**

    ---

    Configure Managed, Non-Interactive, or Invisible Turnstile.

    [:octicons-arrow-right-24: Open the setup guide](providers/cloudflare-turnstile.md)

-   :material-google:{ .lg .middle } **Google reCAPTCHA**

    ---

    Configuration guide for score-based, checkbox, and invisible modes.

    [:octicons-arrow-right-24: View status](providers/google-recaptcha.md)

-   :material-shield-check:{ .lg .middle } **hCaptcha**

    ---

    Configuration guide for checkbox and invisible modes.

    [:octicons-arrow-right-24: View status](providers/hcaptcha.md)

-   :material-login:{ .lg .middle } **WordPress login**

    ---

    Select a provider and protect the WordPress login form.

    [:octicons-arrow-right-24: Configure the form](forms/wordpress-login.md)

</div>

## Current project status

The provider configuration, rendering, and server-side verification foundations
are implemented for Cloudflare Turnstile, Google reCAPTCHA, and hCaptcha.

WordPress login is the currently implemented form integration. Remaining
WordPress and WooCommerce integrations are still planned.

!!! warning "Active development"

    WP Captcha Shield is not ready for production use while the remaining form
    integrations, end-to-end coverage, packaging, and release validation are
    being completed.

## Configuration model

The plugin uses:

- one global default provider;
- per-form overrides;
- a disabled state globally or per form;
- independent provider-mode configuration.

A form can inherit the default provider, choose a specific provider, or disable
CAPTCHA.
