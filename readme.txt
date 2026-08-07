=== WP Captcha Shield ===
Contributors: velukuberan
Tags: captcha, spam, woocommerce, security, recaptcha, hcaptcha, turnstile
Requires at least: 6.9
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 0.1.0-beta1
License: GPL-3.0-or-later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Protect selected WordPress and WooCommerce forms with Cloudflare Turnstile, Google reCAPTCHA Enterprise, or hCaptcha.

== Description ==

WP Captcha Shield lets you protect selected WordPress and WooCommerce forms with a configurable CAPTCHA provider. Choose one global default provider, or override the provider per form.

**Supported providers**

* Cloudflare Turnstile — Managed, Non-Interactive, Invisible
* Google reCAPTCHA Enterprise — Score-based, Checkbox, Invisible
* hCaptcha — Checkbox, Invisible

**Currently protected forms**

WordPress:

* Login
* Registration
* Lost password
* Comments

WooCommerce integrations (login, registration, lost password, product reviews, classic checkout, block checkout) are in active development for the 1.0 release.

**Design principles**

* Provider-neutral configuration: forms select an effective provider without knowing how that provider renders its widget or verifies its token.
* All verification is performed server-side.
* Protected forms fail closed when verification cannot be completed.
* Visitors receive plain, translatable messages. Raw provider errors, tokens, and payloads are never exposed.
* Only the effective provider's scripts are loaded on pages that render a protected form.

For architecture and technical details, see the project repository:
https://github.com/velukuberan/wp-captcha-shield

== Installation ==

1. Download the plugin zip from the GitHub Releases page.
2. In WordPress admin, go to **Plugins → Add New → Upload Plugin** and select the zip.
3. Activate **WP Captcha Shield**.
4. Go to **Settings → WP Captcha Shield** to choose your global provider and enter its site key and secret.
5. Optionally override the provider for individual forms on the same settings page.

**Provider credentials**

You will need an account with your chosen provider and a site/secret key pair:

* Cloudflare Turnstile: https://dash.cloudflare.com/?to=/:account/turnstile
* Google reCAPTCHA Enterprise: https://cloud.google.com/security/products/recaptcha
* hCaptcha: https://www.hcaptcha.com/

== Frequently Asked Questions ==

= Which CAPTCHA provider should I use? =

All three are effective. Cloudflare Turnstile is free, privacy-friendly, and works well as a default. Google reCAPTCHA Enterprise is billed per assessment and is the strongest option if you already use Google Cloud. hCaptcha is a good middle ground and has a generous free tier.

= Does this plugin store submitted CAPTCHA tokens? =

No. Tokens are used only to verify with the provider and are never persisted.

= What happens if the provider is temporarily unreachable? =

Protected forms fail closed. Visitors see a translatable message asking them to try again.

= Does uninstalling remove my settings? =

Yes. Deactivating the plugin preserves your settings and credentials. Uninstalling permanently removes plugin-owned data, including credentials, global defaults, per-form overrides, provider settings, transients, and caches. Uninstallation does not contact external CAPTCHA providers or revoke credentials stored with those providers.

= Can I use multiple providers at the same time? =

You choose one global default provider. Individual forms can override the global default with a different provider, disable CAPTCHA entirely, or fall back to the default.

= Does this plugin work with WooCommerce block checkout? =

Block checkout support is under active development and will land before 1.0. Beta builds may include partial or unstable block-checkout behaviour — see the release notes for the specific beta you install.

== Screenshots ==

1. Global provider selection on the settings page.
2. Per-form override configuration.
3. Provider credentials and mode configuration.

== Known Issues (Beta) ==

* WooCommerce block checkout: invisible and score-based modes for hCaptcha, reCAPTCHA Enterprise, and Cloudflare Turnstile are still being wired up. Checkbox/managed modes work as expected.
* WooCommerce classic checkout, login, registration, lost password, and product review integrations are still being finalised.
* This is a beta release intended for testing. Do not deploy to production.

Please report issues at: https://github.com/velukuberan/wp-captcha-shield/issues

== Changelog ==

= 0.1.0-beta1 =
* Initial public beta.
* Provider support: Cloudflare Turnstile, Google reCAPTCHA Enterprise, hCaptcha.
* Form support: WordPress login, registration, lost password, comments.
* WooCommerce integrations in active development.
* Server-side verification with a common result model (Successful, Failed, Unavailable, Misconfigured).
* Admin settings page for global and per-form configuration.

== Upgrade Notice ==

= 0.1.0-beta1 =
Initial beta. For testing only. Back up your site before installing.