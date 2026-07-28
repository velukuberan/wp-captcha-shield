# WP Captcha Shield

WP Captcha Shield is a WordPress plugin for protecting selected WordPress and WooCommerce forms with configurable CAPTCHA providers.

The plugin uses a provider-neutral configuration model:

- choose one global default CAPTCHA provider;
- override the provider for individual forms;
- disable CAPTCHA globally or for a specific form;
- keep CAPTCHA providers independent from WordPress and WooCommerce form integrations.

> **Project status:** Planning and early development. The plugin is not ready for production use.

## Planned CAPTCHA providers

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

The global CAPTCHA setting can be:

- Disabled
- Cloudflare Turnstile
- Google Cloud Fraud Defense
- hCaptcha

### Per-form override

Each supported form can be configured to:

- use the global default;
- disable CAPTCHA;
- use Cloudflare Turnstile;
- use Google Cloud Fraud Defense;
- use hCaptcha.

`Disabled` is a configuration state, not a CAPTCHA provider.

## Architecture

The project is organized into three primary areas:

```text
Domain/
Providers/
WordPress/
```

The domain remains independent from WordPress and provider-specific implementations. Form integrations depend only on common CAPTCHA contracts, while provider verification results are mapped into a shared result model.

See ARCHITECTURE.md for the complete architectural design and dependency rules.

## Requirements

Minimum supported versions:

- PHP 8.1 or newer
- WordPress 6.9 or newer
- WooCommerce 10.9 or newer

The project uses Composer, namespaces, and PSR-4 autoloading.

See TECHNICAL_REQUIREMENTS.md for the complete platform, tooling, testing, and packaging requirements.

## Security

CAPTCHA verification is performed server-side. Provider secrets never reach browser code, submitted CAPTCHA tokens are treated as untrusted input, and protected forms fail closed when verification cannot be completed.

Raw provider errors, credentials, and CAPTCHA tokens must not be exposed to visitors or written to normal application logs.

## Performance

The plugin is designed to load only the CAPTCHA provider required for the current protected form and to avoid initializing integrations that are not needed for the current request.

## Development

Install the PHP dependencies:

```bash
composer install
composer test
composer test:unit
composer test:integration
composer analyse
composer lint
composer fix
composer check
```

See [CODING_STANDARDS.md](CODING_STANDARDS.md) for coding conventions, testing practices, static-analysis rules, and completion criteria.

## Data cleanup

Deactivating the plugin preserves its settings and credentials.

Uninstalling the plugin permanently removes plugin-owned data. It does not contact external CAPTCHA providers or revoke credentials stored with those providers.

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Technical requirements](TECHNICAL_REQUIREMENTS.md)
- [Coding standards](CODING_STANDARDS.md)

## License

WP Captcha Shield is free software distributed under the terms of the GNU General Public License version 3 or, at your option, any later version.

See [LICENSE](LICENSE) for the complete license text.
