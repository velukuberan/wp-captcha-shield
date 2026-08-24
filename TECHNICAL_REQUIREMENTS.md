# Technical Requirements

## 1. Supported versions

Minimum supported versions:

* PHP 8.1 or newer
* WordPress 6.7.0 or newer
* WooCommerce 10.1.0 or newer when WooCommerce protection is used

WooCommerce is optional. WordPress form protection remains available when WooCommerce is inactive.

Version compatibility comparisons must treat shortened release strings such as `6.7` as equivalent to `6.7.0` where appropriate.

Primary development and release validation should use supported current versions while retaining explicit compatibility testing at the minimum supported versions.

## 2. Composer, namespaces, and autoloading

* Composer is required.
* Production PHP code must use namespaces.
* PSR-4 autoloading must be used.
* The main plugin file must remain a thin WordPress bootstrap.
* Application composition belongs in `src/WordPress/Bootstrap/Plugin.php`.
* Runtime dependencies must remain minimal.
* Development dependencies must not be included in the production ZIP.

## 3. Supported CAPTCHA providers

Version 1 supports:

* Cloudflare Turnstile
* Google reCAPTCHA using the Google Cloud Fraud Defense reCAPTCHA Enterprise assessment API
* hCaptcha

All provider responses must be mapped into the common verification result.

### Cloudflare Turnstile

* Support Managed, Non-Interactive, and Invisible modes.
* Managed is the default and recommended mode.
* Store the site key and secret key.
* Verify tokens server-side.

### Google reCAPTCHA

* Use the reCAPTCHA Enterprise assessment API.
* Support score-based, checkbox, and invisible modes.
* Score-based is the default and recommended mode.
* Version 1 covers bot detection and form-abuse assessment.
* Transaction fraud, payment-risk modelling, and account-takeover modelling are outside version 1.

### hCaptcha

* Support Checkbox and Invisible display modes.
* Checkbox is the default mode.
* Store the site key and secret key.
* Passive behaviour remains controlled by the hCaptcha account and site-key configuration.
* Use the official hCaptcha integration.
* Verify tokens server-side.

## 4. Supported forms

### WordPress

Version 1 supports CAPTCHA protection for:

* Login
* Registration
* Lost password
* Comments

### WooCommerce

Version 1 supports CAPTCHA protection for:

* Login
* Registration
* Lost password
* Product reviews
* Classic checkout
* Checkout Block

WooCommerce product reviews must remain independently configurable from WordPress comments.

Classic Checkout and Checkout Block must share the same user-facing WooCommerce checkout provider setting while retaining separate technical integrations.

## 5. Plugin settings and credentials

Provide one WordPress admin settings page with tabs for:

* General
* Cloudflare Turnstile
* Google reCAPTCHA
* hCaptcha
* Status

Requirements:

* Global default and per-form provider selection
* Separate provider sections
* Hidden stored secret values
* Contextual field guidance where useful
* Warnings or guidance for incomplete provider configuration
* Status information for supported PHP, WordPress, and WooCommerce versions
* Repository-based settings access
* No direct persistence or provider calls from the admin page
* Admin CSS and JavaScript loaded only on the plugin settings page

## 6. Provider HTTP communication

* Providers depend on a small HTTP client abstraction.
* The WordPress implementation uses the WordPress HTTP API.
* Providers must not call `wp_remote_post()` directly.
* Timeouts, network failures, and malformed responses map to `Unavailable`.
* Ordinary automated tests must not call live providers.
* Provider calls occur only after protected form submission.

## 7. Form rendering and token submission

* Use the appropriate WordPress and WooCommerce hooks or platform APIs.
* Render only the effective provider.
* Do not load CAPTCHA when protection is disabled.
* Submit the provider token with the protected action.
* Verify server-side before completing the protected action.
* Keep hook callbacks thin.
* Exact hook names remain implementation details.

Executable provider modes may delay form submission until a token has been generated.

## 8. WooCommerce Classic Checkout

Classic Checkout must:

* render CAPTCHA near and before the Place order action;
* preserve protection through WooCommerce AJAX checkout updates;
* rehydrate provider widgets after relevant checkout markup is replaced;
* prevent asynchronous CAPTCHA execution from racing checkout submission;
* use the shared WooCommerce checkout provider configuration.

Provider-specific rehydration may exist in frontend JavaScript while the PHP checkout integration remains provider-neutral.

## 9. WooCommerce Checkout Block

Checkout Block must:

* integrate with the WooCommerce Store API checkout flow;
* pass CAPTCHA token data through WooCommerce checkout extension data;
* verify CAPTCHA server-side before checkout completion;
* use a provider-neutral browser bridge for checkout token transfer;
* use the same user-facing checkout provider configuration as Classic Checkout.

The Block integration must not introduce provider-specific branching into the WooCommerce integration layer.

## 10. Logging and diagnostics

Version 1 has no dedicated logging subsystem.

* No custom log files
* No database log table
* No browser-side reporting endpoint
* No token, credential, or full provider-payload logging
* Debug-only server diagnostics may use standard WordPress or PHP logging
* Visitors receive simple retry messages
* Configuration problems appear through admin guidance where appropriate

## 11. Internationalization

* Use one consistent text domain: `wp-captcha-shield`.
* Make all admin and visitor strings translatable.
* Pass the text domain explicitly.
* Escape translated output for its final context.
* Map provider failures to plugin-owned translatable messages.
* Maintain the generated translation template at `languages/wp-captcha-shield.pot`.
* Add translator comments where placeholders require context.
* POT generation must exclude generated and development-only directories such as `build`, `vendor`, `tests`, and `coverage`.

## 12. Accessibility

* Use official provider widgets and APIs.
* Preserve provider accessibility controls.
* Keep widgets in normal keyboard order.
* Do not use positive custom `tabindex` values.
* Place widgets near and before the submit button.
* Use accessible WordPress or WooCommerce error mechanisms.
* Make custom errors available to assistive technologies.
* Do not communicate state through colour alone.
* Preserve provider language detection.
* Do not create an automated bypass.
* Do not claim guaranteed legal or accessibility compliance.

## 13. Performance and script loading

* Load scripts only on pages containing a protected form.
* Load only the effective provider.
* Do not load multiple providers unnecessarily.
* Do not initialize WooCommerce integrations when WooCommerce is inactive.
* Load settings once per request and reuse them.
* Avoid repeated `get_option()` calls.
* Keep the main plugin bootstrap lightweight.
* Load admin assets only on the plugin settings page.
* Avoid heavy runtime dependencies.
* Minify production assets.
* Base optimizations on measurement.

## 14. Deactivation, uninstall, and data cleanup

### Deactivation

Preserve all settings, credentials, global defaults, per-form settings, and provider configuration.

### Uninstallation

Remove all plugin-owned data:

* credentials
* global defaults
* per-form settings
* provider settings
* transients
* caches
* future plugin-owned database data

Uninstall must:

* run only through the WordPress uninstall process;
* avoid contacting external providers;
* consider multisite if network activation is supported.

## 15. Testing requirements

The project must include:

* Domain tests;
* provider tests;
* repository tests;
* WordPress integration tests;
* WooCommerce integration tests;
* dedicated coverage for Classic and Block checkout integration boundaries;
* Playwright end-to-end tests for critical browser journeys;
* minimum-version compatibility tests.

Automated E2E tests must not solve live CAPTCHAs.

Provider outcomes must use deterministic test seams.

Real provider verification is tested manually before release.

hCaptcha Invisible must be verified on a real hostname because localhost behaviour is not a reliable release-validation environment for that mode.

## 16. Static analysis and coding standards

Production code must pass the configured:

* PHPUnit test suite;
* PHPStan analysis;
* PHP_CodeSniffer ruleset.

The aggregate local quality command is:

```text
composer check
```

Coverage is generated separately through:

```text
composer test:coverage
```

## 17. Packaging and release requirements

The source repository includes tests, tooling, configuration, fixtures, CI, documentation tooling, and packaging scripts.

The production ZIP excludes:

* tests
* Playwright files
* fixtures
* CI files
* coverage output
* development dependencies
* local environment files
* caches
* unnecessary build tooling

Releases must use a repeatable build process.

The repository includes a release workflow for packaged builds.

Runtime dependencies must be packaged correctly.

Development dependencies must be excluded.

External services, privacy implications, and provider terms must be documented.

Production JavaScript and CSS assets must be minified before final production release packaging.

## 18. Release milestone

The planned Version 1 feature set is implemented and release validation has been completed for the current release candidate.

At this milestone:

* all planned WordPress form integrations are implemented;
* all planned WooCommerce form integrations are implemented;
* Classic Checkout AJAX rehydration is implemented;
* Checkout Block Store API integration is implemented;
* the admin settings UI includes provider tabs and environment status information;
* plugin dependency composition is centralized in the `Plugin` composition root;
* WordPress form registration and WooCommerce registration are coordinated by dedicated bootstraps;
* translation extraction produces `languages/wp-captcha-shield.pot`;
* CI, coverage, documentation deployment, PR labelling, and release workflows exist;
* production JavaScript minification is integrated into the release packaging workflow;
* minimum supported PHP, WordPress, and WooCommerce versions have been manually compatibility-tested;
* supported CAPTCHA providers and modes have been manually validated against live provider services;
* hCaptcha Invisible has been validated on a real hostname;
* Classic Checkout and Checkout Block have been manually validated for logged-in and guest checkout flows.

The planned release-hardening work for the current release candidate is complete.

Further releases may address issues discovered through broader real-world usage while preserving the documented Version 1 architecture, dependency rules, provider boundaries, and supported feature set.

