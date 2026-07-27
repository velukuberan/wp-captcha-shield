# WP Captcha Shield

WP Captcha Shield is a WordPress plugin for protecting selected WordPress and WooCommerce forms with a configurable CAPTCHA provider.

The plugin is designed around a simple provider-neutral model:

- choose one global default provider;
- override the provider for individual forms;
- disable CAPTCHA globally or for a specific form;
- keep provider logic independent from WordPress and WooCommerce form integrations.

> Project status: planning and architecture phase. The plugin is not yet ready for production use.

## Planned providers

- Cloudflare Turnstile
- Google Cloud Fraud Defense using the reCAPTCHA Enterprise assessment API
- hCaptcha

## Planned form support

### WordPress

- Login
- Registration
- Lost password
- Comments

### WooCommerce

- Login
- Registration
- Lost password
- Checkout
- Product reviews

## Configuration model

### Global default

- Disabled
- Cloudflare Turnstile
- Google Cloud Fraud Defense
- hCaptcha

### Per-form override

- Use default
- Disabled
- Cloudflare Turnstile
- Google Cloud Fraud Defense
- hCaptcha

`Disabled` is a configuration state, not a CAPTCHA provider.

## Architectural direction

The project uses three primary areas:

```text
Domain/
Providers/
WordPress/
```

The main architectural boundaries are:

- the domain never knows about WordPress hooks;
- WordPress options are accessed through a repository;
- form integrations never know provider internals;
- providers never know which form invoked them;
- application coordination lives under `WordPress/`;
- provider verification results are mapped to a common result model;
- protected forms fail closed;
- secrets remain server-side;
- CAPTCHA tokens are not stored.

See [`Architecture.txt`](Architecture.txt) for the compact architecture summary.

## Technical requirements

Minimum supported versions:

- PHP 8.1 or newer
- WordPress 6.9 or newer
- WooCommerce 10.9 or newer

Primary development environment:

- WordPress 7.0
- WooCommerce 10.9.4
- the currently installed PHP 8.x version

The project uses Composer, namespaces, and PSR-4 autoloading.

See [`Technical.txt`](Technical.txt) for the complete technical requirements.

## Development quality

Planned development and quality tools include:

- PHPUnit
- Mockery
- Brain Monkey
- PHPStan
- PHPStan strict rules
- PHPStan for WordPress
- PHP_CodeSniffer
- WordPress Coding Standards
- Slevomat Coding Standard
- Playwright for browser-based end-to-end testing

Real CAPTCHA verification will be tested manually. Automated tests will verify the plugin behavior around provider boundaries without attempting to solve live CAPTCHA challenges.

## Performance principles

- Load provider scripts only where a protected form is present.
- Load only the effective provider.
- Avoid repeated option reads within a request.
- Keep the plugin bootstrap lightweight.
- Do not initialize WooCommerce integrations when WooCommerce is inactive.
- Do not contact a provider until a protected form is submitted.

## Security principles

- Verification always occurs server-side.
- Provider secrets never reach browser code.
- Submitted tokens are treated as untrusted input.
- Protected forms fail closed when verification cannot be completed.
- Raw provider errors are never shown to visitors.
- Credentials and CAPTCHA tokens are never written to normal logs.

## Data cleanup

- Deactivation preserves plugin settings and credentials.
- Uninstallation permanently removes all plugin-owned data.
- Uninstallation does not contact or revoke credentials at external providers.

## Packaging

The GitHub repository will contain the complete test suite and development tooling.

The production plugin ZIP will contain only runtime files required by WordPress. Tests, development dependencies, CI files, coverage output, Playwright files, and local tooling will be excluded through a repeatable release process.

## Documentation

- [`ARCHITECTURE.md`](ARCHITECTURE.md)
- [`TECHNICAL_REQUIREMENTS.md`](TECHNICAL_REQUIREMENTS.md)
- [`CODING_STANDARDS.md`](CODING_STANDARDS.md)

## Copyright and License

This package is [free software](https://www.gnu.org/philosophy/free-sw.en.html) distributed under the terms of the GNU General Public License version 3 or (at your option) any later version. For the full license, see [LICENSE](./LICENSE).
