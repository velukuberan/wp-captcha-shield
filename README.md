# WP Captcha Shield

WP Captcha Shield is a WordPress plugin for protecting selected WordPress and WooCommerce forms with configurable CAPTCHA providers.

The plugin uses a provider-neutral configuration model:

- choose one global default CAPTCHA provider;
- override the provider for individual forms;
- disable CAPTCHA globally or for a specific form;
- configure supported modes independently for each provider;
- keep CAPTCHA providers independent from WordPress and WooCommerce form integrations.

> **Project status:** Planning and early development. The plugin is not ready for production use. The architecture and technical design may continue to evolve while the project is in its early stages.

## Planned CAPTCHA providers

### Cloudflare Turnstile

Supported modes:

- Managed — default and recommended
- Non-Interactive
- Invisible

### Google reCAPTCHA

Google reCAPTCHA uses the Google Cloud Fraud Defense reCAPTCHA Enterprise assessment API.

Supported modes:

- Score-based — default and recommended
- Checkbox
- Invisible

Version 1 focuses on bot detection and form-abuse assessment. Transaction fraud, payment-risk modelling, and account-takeover modelling are outside the initial scope.

### hCaptcha

Supported display modes:

- Checkbox — default
- Invisible

Passive behaviour is controlled by the hCaptcha account and site-key configuration rather than by a plugin display-mode setting.

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
- Google reCAPTCHA
- hCaptcha

### Per-form override

Each supported form can be configured to:

- use the global default;
- disable CAPTCHA;
- use Cloudflare Turnstile;
- use Google reCAPTCHA;
- use hCaptcha.

`Disabled` is a configuration state, not a CAPTCHA provider.

Provider selection and provider-specific mode configuration remain separate. Forms select an effective provider without knowing how that provider renders its widget or verifies its token.

## Architecture

The project is organized into three primary areas:

```text
Domain/
Providers/
WordPress/
```

### Domain

Contains provider-neutral configuration rules, provider selection, verification result contracts, and interfaces required by the core rules.

The Domain:

- does not call WordPress or WooCommerce functions;
- does not register or invoke hooks;
- does not perform provider HTTP requests;
- does not know form-specific implementation details.

### Providers

Contains provider-specific rendering and verification behaviour for:

- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha

Providers depend on common Domain contracts and do not know which WordPress or WooCommerce form invoked them.

### WordPress

Contains:

- plugin bootstrap;
- WordPress actions and filters;
- WordPress and WooCommerce form integrations;
- application coordination;
- settings persistence;
- the admin settings page;
- the WordPress HTTP client adapter.

Form integrations depend on shared contracts and do not contain provider-specific implementation branches.

The dependency direction is:

```text
WordPress -> Domain
Providers -> Domain
Domain -X-> WordPress
Domain -X-> Providers
```

See [ARCHITECTURE.md](ARCHITECTURE.md) for the complete architectural design and dependency rules.

## Requirements

Minimum supported versions:

- PHP 8.1 or newer
- WordPress 6.9 or newer
- WooCommerce 10.9 or newer

The primary development and initial test targets are:

- WordPress 7.0
- WooCommerce 10.9.4
- the currently installed supported PHP 8.x version

The project uses Composer, namespaces, and PSR-4 autoloading.

See [TECHNICAL_REQUIREMENTS.md](TECHNICAL_REQUIREMENTS.md) for the complete platform, provider, accessibility, testing, performance, and packaging requirements.

## Verification behaviour

All CAPTCHA verification is performed server-side.

Provider responses are mapped into a common result model:

- Successful
- Failed
- Unavailable
- Misconfigured

Protected forms fail closed when verification cannot be completed.

Visitors receive simple, plugin-owned, translatable messages. Raw provider errors, stack traces, credentials, submitted tokens, and complete provider payloads are not exposed to visitors.

## Security

- Provider secrets remain server-side.
- Submitted CAPTCHA tokens are treated as untrusted input.
- CAPTCHA tokens are not stored.
- Provider verification is performed only after a protected form is submitted.
- Provider HTTP calls use a small HTTP client abstraction.
- Provider implementations do not call WordPress HTTP functions directly.
- CAPTCHA is one anti-abuse control and should not be treated as complete protection by itself.

## Accessibility

The plugin uses official provider widgets and APIs and preserves their accessibility behaviour.

Custom integrations must:

- keep widgets in normal keyboard order;
- avoid positive custom `tabindex` values;
- place widgets near and before the submit button;
- use accessible WordPress and WooCommerce error mechanisms;
- avoid communicating state through colour alone;
- preserve provider language detection.

The project does not claim guaranteed legal or accessibility compliance.

## Performance

The plugin is designed to:

- load scripts only on pages containing a protected form;
- load only the effective CAPTCHA provider;
- avoid initializing WooCommerce integrations when WooCommerce is inactive;
- load settings once per request and reuse them;
- avoid repeated WordPress option reads;
- keep the main plugin bootstrap lightweight;
- load admin assets only on the plugin settings page;
- avoid unnecessary runtime dependencies.

## Development

Install the PHP dependencies:

```bash
composer install
```

Available quality commands:

```bash
composer test
composer test:unit
composer test:integration
composer analyse
composer lint
composer fix
composer check
```

Command responsibilities:

- `composer test` — run the configured PHP test suites;
- `composer test:unit` — run isolated unit tests;
- `composer test:integration` — run integration tests;
- `composer analyse` — run PHPStan;
- `composer lint` — run PHP_CodeSniffer;
- `composer fix` — apply automatically fixable coding-standard changes;
- `composer check` — run all required PHP quality checks.

The project follows a test-first workflow. New behaviour should be introduced with the smallest relevant test, followed by the minimum implementation and then refactoring while the tests remain green.

See [CODING_STANDARDS.md](CODING_STANDARDS.md) for coding conventions, testing practices, static-analysis rules, and completion criteria.

## Testing strategy

The planned test coverage includes:

- Domain unit tests;
- provider tests using deterministic HTTP boundaries;
- settings repository tests;
- WordPress integration tests;
- WooCommerce integration tests;
- Playwright end-to-end tests for critical browser journeys;
- minimum-version compatibility tests;
- manual verification against live CAPTCHA providers.

Automated tests must not call live CAPTCHA services or attempt to solve live CAPTCHA challenges.

## Data cleanup

### Deactivation

Deactivating the plugin preserves:

- settings;
- credentials;
- global defaults;
- per-form overrides;
- provider configuration.

### Uninstallation

Uninstalling the plugin permanently removes plugin-owned data, including:

- credentials;
- global defaults;
- per-form settings;
- provider settings;
- transients;
- caches;
- future plugin-owned database data.

Uninstallation does not contact external CAPTCHA providers or revoke credentials stored with those providers.

## Documentation

- [Architecture](ARCHITECTURE.md)
- [Technical requirements](TECHNICAL_REQUIREMENTS.md)
- [Coding standards](CODING_STANDARDS.md)

The project documents have the following priority when resolving conflicts:

1. `ARCHITECTURE.md`
2. `TECHNICAL_REQUIREMENTS.md`
3. `CODING_STANDARDS.md`
4. `README.md`

## License

WP Captcha Shield is free software distributed under the terms of the GNU General Public License version 3 or, at your option, any later version.

See [LICENSE](LICENSE) for the complete license text.
