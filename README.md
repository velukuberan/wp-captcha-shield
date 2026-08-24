# WP Captcha Shield

WP Captcha Shield is a WordPress plugin for protecting selected WordPress and WooCommerce forms with configurable CAPTCHA providers.

The plugin uses a provider-neutral configuration model:

* choose one global default CAPTCHA provider;
* override the provider for individual forms;
* disable CAPTCHA globally or for a specific form;
* configure supported modes independently for each provider;
* keep CAPTCHA providers independent from WordPress and WooCommerce form integrations.

> **Project status:** Release ready. The planned WordPress and WooCommerce form integrations are implemented, including Classic Checkout and Checkout Block. Provider configuration, rendering, server-side verification, admin status reporting, production packaging, asset minification, translation extraction, minimum-version compatibility testing, and manual live-provider validation are complete.

## CAPTCHA providers

### Cloudflare Turnstile

Implemented modes:

* Managed — default and recommended
* Non-Interactive
* Invisible

Setup guide: [Configure Cloudflare Turnstile](https://velukuberan.github.io/wp-captcha-shield/providers/cloudflare-turnstile/)

### Google reCAPTCHA

Google reCAPTCHA uses the Google Cloud Fraud Defense reCAPTCHA Enterprise assessment API.

Implemented modes:

* Score-based — default and recommended
* Checkbox
* Invisible

Version 1 focuses on bot detection and form-abuse assessment. Transaction fraud, payment-risk modelling, and account-takeover modelling are outside the initial scope.

### hCaptcha

Implemented display modes:

* Checkbox — default
* Invisible

Passive behaviour is controlled by the hCaptcha account and site-key configuration rather than by a plugin display-mode setting.

hCaptcha Invisible has been release-tested on a real hostname because localhost behaviour is not considered a reliable validation environment for that mode.

## Form support

### WordPress

Implemented:

* Login
* Registration
* Lost password
* Comments

### WooCommerce

Implemented:

* Login
* Registration
* Lost password
* Product reviews
* Classic checkout
* Checkout Block

WooCommerce is optional. WordPress form protection remains available when WooCommerce is inactive.

WooCommerce product reviews are configured independently from WordPress comments.

Classic Checkout and Checkout Block share one user-facing WooCommerce checkout CAPTCHA setting while using separate technical integrations.

### Classic Checkout

Classic Checkout integrates with WooCommerce's traditional PHP/AJAX checkout flow.

The CAPTCHA is placed near the Place order button and is rehydrated after WooCommerce `updated_checkout` refreshes replace checkout markup.

### Checkout Block

Checkout Block integrates with WooCommerce Blocks and the Store API.

CAPTCHA token data is passed through WooCommerce checkout extension data and verified server-side before checkout completes.

## Configuration model

### Global default

The global CAPTCHA setting can be:

* Disabled
* Cloudflare Turnstile
* Google reCAPTCHA
* hCaptcha

### Per-form override

Each supported form can be configured to:

* use the global default;
* disable CAPTCHA;
* use Cloudflare Turnstile;
* use Google reCAPTCHA;
* use hCaptcha.

`Disabled` is a configuration state, not a CAPTCHA provider.

Provider selection and provider-specific mode configuration remain separate.

## Admin settings

WP Captcha Shield provides one settings page with tabs for:

* General
* Cloudflare Turnstile
* Google reCAPTCHA
* hCaptcha
* Status

The settings UI includes contextual field guidance and an environment status table.

The Status tab compares the current environment with the minimum supported versions while preserving the raw version reported by WordPress, PHP, or WooCommerce.

Short versions such as `6.7` are normalized for compatibility comparison with `6.7.0`.

## Architecture

The project is organized into three primary areas:

```text
Domain/
Providers/
WordPress/
```

### Domain

Contains provider-neutral configuration rules, provider selection, verification results, contracts, and environment compatibility logic.

The Domain:

* does not call WordPress or WooCommerce functions;
* does not register hooks;
* does not perform provider HTTP requests;
* does not know form-specific implementation details.

### Providers

Contains provider-specific verification behaviour for:

* Cloudflare Turnstile
* Google reCAPTCHA
* hCaptcha

Providers depend on Domain contracts and do not know which WordPress or WooCommerce form invoked them.

### WordPress

Contains:

* the `Plugin` composition root;
* WordPress actions and filters;
* WordPress and WooCommerce form integrations;
* `WordPressFormsBootstrap`;
* `WooCommerceBootstrap`;
* application coordination;
* CAPTCHA widget rendering;
* settings persistence;
* the admin settings UI;
* the WordPress HTTP client adapter.

`wp-captcha-shield.php` remains a thin bootstrap and delegates application startup to the `Plugin` composition root.

The dependency direction is:

```text
WordPress -> Domain
Providers -> Domain
Domain -X-> WordPress
Domain -X-> Providers
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for the complete architectural design and locked invariants.

## Requirements

Minimum supported versions:

* PHP 8.1 or newer
* WordPress 6.7.0 or newer
* WooCommerce 10.1.0 or newer when WooCommerce integration is used

WooCommerce is optional.

The project uses Composer, namespaces, and PSR-4 autoloading.

See [TECHNICAL_REQUIREMENTS.md](TECHNICAL_REQUIREMENTS.md) for the complete platform, provider, accessibility, testing, performance, packaging, and release requirements.

## Verification behaviour

All CAPTCHA verification is performed server-side.

Provider responses are mapped into a common result model:

* Successful
* Failed
* Unavailable
* Misconfigured

Protected forms fail closed when verification cannot be completed.

Visitors receive simple, plugin-owned, translatable messages.

Raw provider errors, stack traces, credentials, submitted tokens, and complete provider payloads are not exposed to visitors.

## Security

* Provider secrets remain server-side.
* Submitted CAPTCHA tokens are treated as untrusted input.
* CAPTCHA tokens are not stored.
* Provider verification is performed only after a protected action is submitted.
* Provider HTTP calls use a small HTTP client abstraction.
* Provider implementations do not call WordPress HTTP functions directly.
* CAPTCHA is one anti-abuse control and should not be treated as complete protection by itself.

## Accessibility

The plugin uses official provider widgets and APIs and preserves their accessibility behaviour.

Custom integrations must:

* keep widgets in normal keyboard order;
* avoid positive custom `tabindex` values;
* place widgets near and before the submit button;
* use accessible WordPress and WooCommerce error mechanisms;
* avoid communicating state through colour alone;
* preserve provider language detection.

The project does not claim guaranteed legal or accessibility compliance.

## Internationalization

WP Captcha Shield uses the text domain:

```text
wp-captcha-shield
```

The generated translation template is:

```text
languages/wp-captcha-shield.pot
```

POT generation excludes generated and development-only directories such as `build`, `vendor`, `tests`, and `coverage`.

Translator comments are added where placeholder strings need extraction context.

## Performance

The plugin is designed to:

* load scripts only on pages containing a protected form;
* load only the effective CAPTCHA provider;
* avoid initializing WooCommerce integrations when WooCommerce is inactive;
* load settings once per request and reuse them;
* avoid repeated WordPress option reads;
* keep the main plugin bootstrap lightweight;
* load admin assets only on the plugin settings page;
* avoid unnecessary runtime dependencies;
* use minified assets in production builds.

## Development

Install PHP dependencies:

```bash
composer install
```

Available quality commands:

```bash
composer test
composer test:unit
composer test:integration
composer test:coverage
composer analyse
composer lint
composer fix
composer check
```

Command responsibilities:

* `composer test` — run the complete PHPUnit test suite;
* `composer test:unit` — run isolated unit tests;
* `composer test:integration` — run integration tests;
* `composer test:coverage` — run unit tests and generate a Clover coverage report;
* `composer analyse` — run PHPStan;
* `composer lint` — run PHP_CodeSniffer;
* `composer fix` — apply automatically fixable coding-standard changes;
* `composer check` — run all required PHP quality checks.

The project follows a test-first workflow.

See [CODING_STANDARDS.md](CODING_STANDARDS.md) for coding conventions, testing practices, static-analysis rules, localization rules, coverage expectations, and completion criteria.

## Testing strategy

The PHP test suite covers the implemented:

* Domain behaviour;
* environment compatibility behaviour;
* provider verification behaviour;
* settings persistence and configuration;
* WordPress HTTP and application adapters;
* shared CAPTCHA widget rendering;
* WordPress login;
* WordPress registration;
* WordPress lost password;
* WordPress comments;
* WooCommerce bootstrap and availability;
* WooCommerce login;
* WooCommerce registration;
* WooCommerce lost password;
* WooCommerce product reviews;
* WooCommerce Classic Checkout;
* WooCommerce Checkout Block;
* admin settings page components.

Release validation includes:

* Playwright end-to-end coverage for critical browser journeys;
* minimum-version compatibility testing;
* manual verification against live CAPTCHA providers;
* real-hostname verification for hCaptcha Invisible.

Automated tests must not call live CAPTCHA services or attempt to solve live CAPTCHA challenges.

## Code coverage

PHPUnit generates code-coverage data for production code under `src/`.

Run unit-test coverage locally with:

```bash
composer test:coverage
```

The command generates:

```text
coverage/clover.xml
```

Generated coverage output must not be committed.

GitHub Actions contains a dedicated coverage workflow path using PCOV and Codecov.

Coverage percentages do not replace behavioural testing, architecture review, static analysis, coding-standard checks, WordPress/WooCommerce integration tests, or manual provider verification.

## Packaging and release

The repository contains CI and release automation, including:

* PHP CI;
* coverage reporting;
* documentation deployment;
* pull-request labelling;
* release packaging.

The production package excludes development-only files and dependencies according to the project packaging rules.

Production JavaScript is minified automatically by the release packaging workflow.

## Data cleanup

### Deactivation

Deactivating the plugin preserves:

* settings;
* credentials;
* global defaults;
* per-form overrides;
* provider configuration.

### Uninstallation

Uninstalling the plugin permanently removes plugin-owned data, including:

* credentials;
* global defaults;
* per-form settings;
* provider settings;
* transients;
* caches;
* future plugin-owned database data.

Uninstallation does not contact external CAPTCHA providers or revoke credentials stored with those providers.

## Documentation

* [Architecture](ARCHITECTURE.md)
* [Technical requirements](TECHNICAL_REQUIREMENTS.md)
* [Coding standards](CODING_STANDARDS.md)
* [Contributing](CONTRIBUTING.md)
* [User documentation](https://velukuberan.github.io/wp-captcha-shield/)

The project documents have the following priority when resolving conflicts:

1. `ARCHITECTURE.md`
2. `TECHNICAL_REQUIREMENTS.md`
3. `CODING_STANDARDS.md`
4. `README.md`

## License

WP Captcha Shield is free software distributed under the terms of the GNU General Public License version 3 or, at your option, any later version.

See [LICENSE](LICENSE) for the complete license text.

