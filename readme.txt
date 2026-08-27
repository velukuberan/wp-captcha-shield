=== WP Captcha Shield ===
Contributors: velukuberan
Tags: captcha, spam, woocommerce, security, recaptcha, hcaptcha, turnstile
Requires at least: 6.7.0
Tested up to: 7.0
Requires PHP: 8.1
Stable tag: 1.0.0
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Protect selected WordPress and WooCommerce forms with Cloudflare Turnstile, Google reCAPTCHA Enterprise, or hCaptcha.

== Description ==

WP Captcha Shield protects selected WordPress and WooCommerce forms using configurable CAPTCHA providers.

Choose one global default provider, then optionally override the provider for individual forms or disable CAPTCHA for a specific form.

Supported providers

Cloudflare Turnstile — Managed, Non-Interactive, Invisible
Google reCAPTCHA Enterprise — Score-based, Checkbox, Invisible
hCaptcha — Checkbox, Invisible

Supported WordPress forms

Login
Registration
Lost password
Comments

Supported WooCommerce forms

Login
Registration
Lost password
Product reviews
Classic checkout
Checkout Block

WooCommerce is optional. WordPress form protection remains available when WooCommerce is not active.

WooCommerce 10.1.0 or newer is required when WooCommerce protection is used.

Configuration

WP Captcha Shield provides one settings page under Settings → WP Captcha Shield.

The General tab lets you:

choose a global default provider;
use the global default for individual forms;
select a different provider for an individual form;
disable CAPTCHA globally or for an individual form.

Separate settings tabs are available for Cloudflare Turnstile, Google reCAPTCHA, and hCaptcha.

The Status tab compares the current PHP, WordPress, and WooCommerce versions with the minimum versions supported by WP Captcha Shield.

Security and verification

CAPTCHA verification is performed server-side.
Protected forms fail closed when required verification cannot be completed.
Submitted CAPTCHA tokens are treated as untrusted input and are not stored.
Provider secret credentials remain server-side.
Raw provider errors, tokens, credentials, and complete provider responses are not exposed to visitors.
Only the effective CAPTCHA provider is loaded for a protected form.

Documentation

Detailed setup, provider configuration, form guides, and troubleshooting documentation:

https://velukuberan.github.io/wp-captcha-shield/

Project source and technical documentation:

https://github.com/velukuberan/wp-captcha-shield

== Installation ==

Install WP Captcha Shield from Plugins → Add New, or upload the plugin package using Plugins → Add New → Upload Plugin.
Activate WP Captcha Shield.
Go to Settings → WP Captcha Shield.
Configure the credentials for at least one CAPTCHA provider.
On the General tab, select the provider you want to use as the global default.
Optionally override or disable CAPTCHA for individual WordPress or WooCommerce forms.
Test the protected forms on your site before relying on them in production.

Provider setup

Cloudflare Turnstile:
https://dash.cloudflare.com/?to=/:account/turnstile

Google reCAPTCHA Enterprise:
https://cloud.google.com/security/products/recaptcha

hCaptcha:
https://www.hcaptcha.com/

Detailed setup guides are available at:

https://velukuberan.github.io/wp-captcha-shield/

== Frequently Asked Questions ==

= Which CAPTCHA providers are supported? =

WP Captcha Shield supports Cloudflare Turnstile, Google reCAPTCHA Enterprise, and hCaptcha.

Cloudflare Turnstile supports Managed, Non-Interactive, and Invisible modes.

Google reCAPTCHA Enterprise supports Score-based, Checkbox, and Invisible modes.

hCaptcha supports Checkbox and Invisible display modes.

= Which provider should I use? =

The best provider depends on your requirements.

Cloudflare Turnstile Managed is the default recommended Turnstile mode and provides a low-friction starting point for many sites.

Google reCAPTCHA Enterprise is available for sites that want Google Cloud reCAPTCHA assessment-based protection.

hCaptcha provides Checkbox and Invisible display modes.

You can also use different providers for different forms.

= Can I use multiple providers at the same time? =

Yes.

You choose one global default provider, but each supported form can use the global default, select a different provider, or disable CAPTCHA.

Only the provider configured for a particular protected form is used for that form.

= Does this plugin work without WooCommerce? =

Yes.

WooCommerce is optional. WordPress login, registration, lost password, and comment protection remain available when WooCommerce is not installed or active.

= Which WooCommerce versions are supported? =

WooCommerce 10.1.0 or newer is required for WooCommerce protection.

= Does this plugin support WooCommerce Classic Checkout? =

Yes.

WP Captcha Shield integrates with the traditional WooCommerce Classic Checkout flow and preserves CAPTCHA protection when WooCommerce updates checkout content through AJAX.

= Does this plugin support WooCommerce Checkout Block? =

Yes.

WP Captcha Shield supports WooCommerce Checkout Block using the Store API checkout flow. CAPTCHA token data is passed through WooCommerce checkout extension data and verified server-side before checkout completes.

Classic Checkout and Checkout Block use the same WooCommerce checkout CAPTCHA setting.

= Are WooCommerce product reviews the same setting as WordPress comments? =

No.

WooCommerce product reviews are independently configurable from WordPress comments.

= Does this plugin store submitted CAPTCHA tokens? =

No.

CAPTCHA tokens are used for provider verification and are not persisted by WP Captcha Shield.

= What happens if the CAPTCHA provider cannot be reached? =

Protected forms fail closed when required verification cannot be completed.

Visitors receive a plugin-owned message asking them to try again. Raw provider errors and technical responses are not exposed.

= Does deactivating the plugin remove my settings? =

No.

Deactivating WP Captcha Shield preserves its settings, credentials, global default, per-form overrides, and provider configuration.

= Does uninstalling the plugin remove my settings? =

Yes.

Uninstalling WP Captcha Shield permanently removes plugin-owned data, including credentials, global defaults, per-form settings, provider settings, transients, and caches.

Uninstallation does not contact external CAPTCHA providers or revoke credentials stored with those providers.

= Does WP Captcha Shield use external services? =

Yes.

CAPTCHA protection depends on the external provider selected for a protected form.

When a provider is enabled, its official browser-side CAPTCHA service may be loaded on pages containing a protected form. When the form is submitted, CAPTCHA verification data is sent to that provider for server-side verification.

See the External services section below for details.

= Where can I find detailed setup instructions? =

User documentation is available at:

https://velukuberan.github.io/wp-captcha-shield/

== External services ==

WP Captcha Shield integrates with third-party CAPTCHA services. These services are required only when you configure and use the corresponding provider.

A provider's browser-side CAPTCHA service may process information directly from the visitor's browser according to that provider's own policies.

WP Captcha Shield also sends CAPTCHA verification information to the selected provider when a protected action is submitted.

=== Cloudflare Turnstile ===

When Cloudflare Turnstile protects a form, the Turnstile browser service is loaded so Cloudflare can perform its CAPTCHA challenge and generate a verification token.

When the protected form is submitted, WP Captcha Shield sends the generated token and the configured secret key to Cloudflare's Siteverify service. The visitor's IP address may also be included when available.

No form-field contents are intentionally included by WP Captcha Shield in the server-side Siteverify request.

Service:
https://www.cloudflare.com/products/turnstile/

Turnstile documentation:
https://developers.cloudflare.com/turnstile/

Turnstile Privacy Addendum:
https://www.cloudflare.com/turnstile-privacy-policy/

Cloudflare terms:
https://www.cloudflare.com/website-terms/

=== Google reCAPTCHA Enterprise ===

When Google reCAPTCHA protects a form, Google's reCAPTCHA browser service is used to generate a CAPTCHA token.

When the protected form is submitted, WP Captcha Shield creates a Google reCAPTCHA Enterprise assessment.

The assessment can include:

the generated CAPTCHA token;
the configured site key;
the visitor's IP address when available;
the visitor's user agent when available;
the expected CAPTCHA action when applicable.

The Google Cloud project ID and configured API key are used to authenticate the assessment request.

Service:
https://cloud.google.com/security/products/recaptcha

Documentation:
https://cloud.google.com/recaptcha/docs

Google Cloud Terms of Service:
https://cloud.google.com/terms

Google Cloud privacy information:
https://cloud.google.com/privacy

=== hCaptcha ===

When hCaptcha protects a form, the hCaptcha browser service is loaded so hCaptcha can perform its challenge and generate a verification token.

When the protected form is submitted, WP Captcha Shield sends the generated token, configured site key, and configured secret key to hCaptcha's Siteverify service. The visitor's IP address may also be included when available.

No form-field contents are intentionally included by WP Captcha Shield in the server-side Siteverify request.

Service:
https://www.hcaptcha.com/

Documentation:
https://docs.hcaptcha.com/

Privacy Policy:
https://www.hcaptcha.com/privacy

Terms of Service:
https://www.hcaptcha.com/terms

Site owners are responsible for reviewing the terms, privacy requirements, and configuration requirements of the CAPTCHA provider they choose to use.

== Screenshots ==

General settings with global provider selection and per-form CAPTCHA overrides.
Cloudflare Turnstile credentials and widget mode configuration.
Google reCAPTCHA Enterprise credentials, mode, and minimum-score configuration.
hCaptcha credentials and display-mode configuration.
CAPTCHA protection on a WordPress form.
CAPTCHA protection on a WooCommerce account form.
CAPTCHA protection on WooCommerce checkout.

== Support ==

Please report bugs and technical issues at:

https://github.com/velukuberan/wp-captcha-shield/issues

User documentation and troubleshooting guides are available at:

https://velukuberan.github.io/wp-captcha-shield/

== Changelog ==

= 1.0.0 =

First stable release.
Added CAPTCHA protection for WordPress login, registration, lost password, and comments.
Added WooCommerce protection for login, registration, lost password, product reviews, Classic Checkout, and Checkout Block.
Added Cloudflare Turnstile support for Managed, Non-Interactive, and Invisible modes.
Added Google reCAPTCHA Enterprise support for Score-based, Checkbox, and Invisible modes.
Added hCaptcha support for Checkbox and Invisible modes.
Added global provider selection and independent per-form provider overrides.
Added server-side verification with shared verification results.
Added independent WooCommerce product-review configuration.
Added Classic Checkout AJAX rehydration.
Added Checkout Block Store API integration.
Added admin environment compatibility status.
Added production release packaging with minified frontend assets.
Added translation support and translation-template generation.
Validated supported providers and modes against live provider services.
Validated minimum and current supported WordPress, WooCommerce, and PHP environments.

= 0.1.0-beta1 =

Initial public beta.
Added Cloudflare Turnstile, Google reCAPTCHA Enterprise, and hCaptcha providers.
Added WordPress login, registration, lost password, and comment protection.
Added server-side verification using a common verification-result model.
Added global and per-form CAPTCHA configuration.

== Upgrade Notice ==

= 1.0.0 =

First stable release with WordPress and WooCommerce CAPTCHA protection, including Classic Checkout and Checkout Block.

= 0.1.0-beta1 =

Initial public beta.
