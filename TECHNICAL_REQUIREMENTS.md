# Technical Requirements

## 1. Supported versions

Minimum supported versions:

- PHP 8.1 or newer
- WordPress 6.9 or newer
- WooCommerce 10.9 or newer

Primary development and initial test environment:

- WordPress 7.0
- WooCommerce 10.9.4
- The currently installed PHP 8.x version

Compatibility testing must also cover the minimum supported versions.

## 2. Composer, namespaces, and autoloading

- Composer is required.
- Production PHP code must use namespaces.
- PSR-4 autoloading must be used.
- The main plugin file must remain a thin WordPress bootstrap.
- Runtime dependencies must remain minimal.
- Development dependencies must not be included in the production ZIP.

## 3. Supported CAPTCHA providers

Version 1 supports:

- Cloudflare Turnstile
- Google reCAPTCHA using the Google Cloud Fraud Defense reCAPTCHA Enterprise assessment API
- hCaptcha

All provider responses must be mapped into the common verification result.

### Cloudflare Turnstile

- Support Managed, Non-Interactive, and Invisible modes.
- Managed is the default and recommended mode.
- Store the site key and secret key.
- Verify tokens server-side.

### Google reCAPTCHA

- Use the reCAPTCHA Enterprise assessment API.
- Support score-based, checkbox, and invisible modes.
- Score-based is the default and recommended mode.
- Version 1 covers bot detection and form-abuse assessment.
- Transaction fraud, payment-risk modelling, and account-takeover modelling are outside version 1.

### hCaptcha

- Support Checkbox and Invisible display modes.
- Checkbox is the default mode.
- Store the site key and secret key.
- Passive behaviour remains controlled by the hCaptcha account and site-key configuration.
- Use the official hCaptcha integration.
- Verify tokens server-side.

## 4. Plugin settings and credentials

Provide one WordPress admin settings page with:

- General settings
- Cloudflare Turnstile settings
- Google reCAPTCHA settings
- hCaptcha settings

Requirements:

- Global default and per-form provider selection
- Separate provider sections
- Hidden stored secret values
- Warnings for incomplete provider configuration
- Repository-based settings access
- No direct persistence or provider calls from the admin page

## 5. Provider HTTP communication

- Providers depend on a small HTTP client abstraction.
- The WordPress implementation uses the WordPress HTTP API.
- Providers must not call `wp_remote_post()` directly.
- Timeouts, network failures, and malformed responses map to `Unavailable`.
- Ordinary automated tests must not call live providers.
- Provider calls occur only after protected form submission.

## 6. Form rendering and token submission

- Use the appropriate WordPress and WooCommerce hooks.
- Render only the effective provider.
- Do not load CAPTCHA when protection is disabled.
- Submit the provider token with the form.
- Verify server-side before completing the protected action.
- Keep hook callbacks thin.
- Exact hook names remain implementation details.

## 7. Logging and diagnostics

Version 1 has no dedicated logging subsystem.

- No custom log files
- No database log table
- No browser-side reporting endpoint
- No token, credential, or full provider-payload logging
- Debug-only server diagnostics may use standard WordPress or PHP logging
- Visitors receive simple retry messages
- Configuration problems appear as admin warnings

## 8. Internationalization

- Use one consistent text domain.
- Make all admin and visitor strings translatable.
- Pass the text domain explicitly.
- Escape translated output for its final context.
- Map provider failures to plugin-owned translatable messages.

## 9. Accessibility

- Use official provider widgets and APIs.
- Preserve provider accessibility controls.
- Keep widgets in normal keyboard order.
- Do not use positive custom `tabindex` values.
- Place widgets near and before the submit button.
- Use accessible WordPress or WooCommerce error mechanisms.
- Make custom errors available to assistive technologies.
- Do not communicate state through colour alone.
- Preserve provider language detection.
- Do not create an automated bypass.
- Do not claim guaranteed legal or accessibility compliance.

## 10. Performance and script loading

- Load scripts only on pages containing a protected form.
- Load only the effective provider.
- Do not load multiple providers unnecessarily.
- Do not initialize WooCommerce integration when WooCommerce is inactive.
- Load settings once per request and reuse them.
- Avoid repeated `get_option()` calls.
- Keep bootstrap lightweight.
- Load admin assets only on the plugin settings page.
- Avoid heavy runtime dependencies.
- Minify production assets.
- Base optimizations on measurement.

## 11. Deactivation, uninstall, and data cleanup

### Deactivation

Preserve all settings, credentials, and provider configuration.

### Uninstallation

Remove all plugin-owned data:

- credentials
- global defaults
- per-form settings
- provider settings
- transients
- caches
- future plugin-owned database data

Uninstall must:

- run only through the WordPress uninstall process;
- avoid contacting external providers;
- consider multisite if network activation is supported.

## 12. Testing requirements

The project must include:

- domain tests;
- provider tests;
- repository tests;
- WordPress integration tests;
- WooCommerce integration tests;
- Playwright end-to-end tests;
- minimum-version compatibility tests.

Automated E2E tests must not solve live CAPTCHAs. Provider outcomes must use deterministic test seams. Real provider verification is tested manually before release.

## 13. Packaging and WordPress.org requirements

The source repository includes tests, tooling, configuration, fixtures, CI, and packaging scripts.

The production ZIP excludes:

- tests
- Playwright files
- fixtures
- CI files
- coverage output
- development dependencies
- local environment files
- caches
- unnecessary build tooling

Releases must use a repeatable build process. Runtime dependencies must be packaged correctly. Development dependencies must be excluded. External services, privacy implications, and provider terms must be documented. GitHub Actions may later run tests, static analysis, coding standards, E2E checks, packaging validation, and release generation.
