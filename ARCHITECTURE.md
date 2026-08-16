# Architecture

## 1. Architectural stance

WP Captcha Shield uses a simple layered architecture with SOLID principles, explicit boundaries, and a test-first development approach.

The project is not a full Domain-Driven Design implementation. Domain concepts are used only where they provide clear separation between provider-independent rules, infrastructure, and WordPress/WooCommerce integration code.

## 2. Top-level areas

Production code is divided into three primary areas:

```text
src/
├── Domain/
├── Providers/
└── WordPress/
```

### Domain

Contains provider-neutral rules, contracts, configuration concepts, environment compatibility logic, and verification results.

The Domain must not depend on WordPress, WooCommerce, or provider implementations.

### Providers

Contains Cloudflare Turnstile, Google reCAPTCHA, and hCaptcha implementations.

Providers depend on Domain contracts and remain unaware of the WordPress or WooCommerce form that initiated verification.

### WordPress

Contains application composition, WordPress and WooCommerce integration code, persistence adapters, HTTP adapters, admin UI, form rendering, hook registration, and runtime coordination.

## 3. Dependency rule

Allowed dependency direction:

```text
WordPress -> Domain
Providers -> Domain
```

Forbidden dependency direction:

```text
Domain -X-> WordPress
Domain -X-> Providers
Providers -X-> WordPress form integrations
```

WordPress may compose Domain and Provider collaborators, but the core Domain must remain independent.

## 4. Composition root and bootstrap

`wp-captcha-shield.php` is a thin WordPress entry point.

Its responsibilities are limited to:

- declaring plugin metadata;
- defining required plugin constants;
- loading Composer autoloading;
- delegating startup to `WordPress\Bootstrap\Plugin`.

`src/WordPress/Bootstrap/Plugin.php` is the application composition root.

The composition root creates and wires concrete implementations for:

- settings persistence;
- HTTP communication;
- provider verification;
- provider selection;
- CAPTCHA rendering;
- WordPress form integrations;
- WooCommerce integrations;
- admin settings.

Dependency construction belongs in the composition root rather than in form integrations or Domain code.

## 5. WordPress and WooCommerce bootstraps

Core WordPress form registration is coordinated by:

```text
src/WordPress/Forms/WordPressFormsBootstrap.php
```

WooCommerce initialization is coordinated independently through:

```text
src/WordPress/WooCommerce/WooCommerceBootstrap.php
```

WooCommerce-specific integration must remain inactive when WooCommerce is unavailable.

WooCommerce initialization is deferred until the appropriate WordPress plugin-loading stage.

## 6. Configuration model

The plugin has one global default CAPTCHA provider.

Global default values:

- Disabled
- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha

Each supported form has an independent override:

- Use default
- Disabled
- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha

`Disabled` is a configuration state and not a provider implementation.

Provider selection and provider mode configuration are separate concerns.

## 7. Supported form integrations

### WordPress

Implemented form integrations:

- Login
- Registration
- Lost password
- Comments

### WooCommerce

Implemented form integrations:

- Login
- Registration
- Lost password
- Product reviews
- Classic checkout
- Checkout Block

WooCommerce product reviews are deliberately separate from WordPress comments so they can be configured independently.

Classic checkout and Checkout Block share one user-facing WooCommerce checkout provider setting while retaining separate technical integrations.

## 8. Form integration boundary

Forms must not know provider internals.

A form integration may:

- determine whether protection is enabled;
- request rendering through shared CAPTCHA rendering services;
- extract submitted token data;
- request verification through provider-neutral services;
- translate verification outcomes into the native WordPress or WooCommerce validation mechanism.

A form integration must not:

- branch on Cloudflare, Google, or hCaptcha implementation details;
- perform provider HTTP requests directly;
- persist provider settings directly.

Adding a provider must not require modifying individual form integrations.

Adding a form must not require modifying provider implementations.

## 9. CAPTCHA rendering

Shared CAPTCHA rendering lives in the WordPress form layer and resolves the effective provider before delegating provider-specific widget behaviour.

Provider-specific frontend implementations remain behind the common rendering boundary.

Executable CAPTCHA modes may require JavaScript coordination before submission. This coordination must remain provider-neutral from the form integration's perspective.

## 10. WooCommerce checkout architecture

### Classic checkout

Classic checkout uses WooCommerce's traditional PHP form and AJAX refresh lifecycle.

The CAPTCHA is rendered near the Place order action and must be rehydrated after WooCommerce checkout updates replace relevant markup.

The rehydration script may call provider APIs, but the PHP checkout integration remains provider-neutral.

### Checkout Block

Checkout Block uses WooCommerce Blocks and the Store API.

CAPTCHA token data is transferred through WooCommerce checkout extension data and verified server-side before checkout processing completes.

The browser bridge is responsible for moving CAPTCHA token state into the Store API request without introducing provider-specific branching into the WooCommerce checkout integration.

Classic and Block checkout are separate adapters around one shared checkout configuration.

## 11. Provider boundary

Each CAPTCHA provider owns:

- provider-specific credentials;
- provider-specific mode configuration;
- provider endpoint knowledge;
- provider request construction;
- provider response interpretation.

Each provider maps its result into the shared verification result model.

Providers never know which WordPress or WooCommerce form requested verification.

## 12. HTTP boundary

Provider HTTP communication depends on a small Domain-facing HTTP abstraction.

The WordPress layer supplies the concrete adapter backed by the WordPress HTTP API.

Provider implementations must not call WordPress HTTP functions directly.

## 13. Verification and failure model

Common verification outcomes are:

- Successful
- Failed
- Unavailable
- Misconfigured

Protected forms fail closed.

Expected provider or verification failures are represented using the common result model rather than provider-specific errors escaping into form integrations.

Visitors receive plugin-owned safe messages.

Technical provider responses, credentials, tokens, stack traces, and internal exceptions remain internal.

## 14. Settings persistence

WordPress option storage is accessed through a settings repository.

Domain and Provider code must not call WordPress option functions directly.

Settings should be loaded once per request where practical and reused by collaborators.

Deactivation preserves plugin configuration.

Uninstallation removes plugin-owned data.

## 15. Admin settings architecture

The admin settings page is decomposed into focused components.

`SettingsPage` coordinates WordPress-specific page behaviour rather than rendering all content itself.

Rendering responsibilities are separated into:

- `SettingsPageView`;
- `SettingsFieldRenderer`;
- `SettingsTabSection`;
- one section class per settings tab.

Current tabs are:

- General
- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha
- Status

Environment version comparison belongs to the Domain through `EnvironmentCompatibility`.

The admin UI may display WordPress/WooCommerce environment information, but compatibility rules must not become coupled to rendering code.

## 16. Environment compatibility

Environment compatibility comparison is provider-independent Domain behaviour.

Version strings reported without a patch component, such as `6.7`, are normalized for comparison so they are treated consistently with the equivalent `6.7.0`.

The raw environment version remains a presentation concern and may still be displayed exactly as reported.

## 17. Internationalization boundary

Visitor-facing and admin-facing strings are plugin-owned translatable strings.

The generated translation template lives at:

```text
languages/wp-captcha-shield.pot
```

Translation extraction is a build/documentation concern and must not introduce runtime dependencies into Domain or Provider code.

## 18. Security invariants

- CAPTCHA verification is server-side.
- Provider secrets remain server-side.
- Submitted CAPTCHA tokens are untrusted.
- CAPTCHA tokens are not stored.
- Forms fail closed when required verification cannot be completed.
- Raw provider errors are never exposed to visitors.
- Provider HTTP traffic crosses the configured HTTP abstraction.

## 19. Testing architecture

Tests follow the same architectural boundaries as production code.

- Domain tests run without WordPress.
- Provider tests use deterministic HTTP collaborators and do not call live providers.
- WordPress tests cover hooks, adapters, persistence, rendering, and form integrations.
- WooCommerce tests cover WooCommerce-specific integration boundaries.
- Classic and Block checkout are tested independently where their platform mechanisms differ.
- Live provider behaviour is verified manually.
- Browser E2E tests must not solve live CAPTCHA challenges.

Production architecture must not be weakened solely for test tooling.

## 20. Locked invariants

1. `Domain/` never depends on WordPress, WooCommerce, or `Providers/`.
2. Providers never know forms.
3. Forms never contain provider-specific implementation branches.
4. WordPress options are accessed through the settings repository.
5. Provider HTTP communication crosses the HTTP client abstraction.
6. `wp-captcha-shield.php` remains a thin bootstrap.
7. Application dependency construction belongs in the `Plugin` composition root.
8. WordPress and WooCommerce form bootstrapping remain separate.
9. Classic Checkout and Checkout Block remain separate technical integrations while sharing one checkout configuration.
10. Protected forms verify CAPTCHA server-side and fail closed.
11. Secrets and CAPTCHA tokens are never exposed or persisted.
12. Production design is not changed solely to accommodate mocks, coverage tools, or testing-library limitations.
