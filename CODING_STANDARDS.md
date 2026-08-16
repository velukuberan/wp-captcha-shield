# Coding Standards

## 1. General principles

- Keep modules small, cohesive, and independently testable.
- A small change should require understanding only the relevant module and its contract.
- Follow SOLID without creating abstractions for appearance.
- Preserve the `Domain/`, `Providers/`, and `WordPress/` boundaries.
- Prefer explicit dependencies over globals, service locators, and hidden state.
- Do not mix unrelated refactoring with feature work.
- Keep production design independent of testing-library limitations.

## 2. PHP and project structure

- PHP 8.1 or newer is required.
- Use namespaces and PSR-4 autoloading.
- Composer is the project autoloader.
- Keep `wp-captcha-shield.php` as a thin WordPress bootstrap.
- Application dependency construction belongs in `src/WordPress/Bootstrap/Plugin.php`.
- Use strict types where practical.
- Prefer typed properties, parameters, and return types.
- Keep public APIs small and intentional.
- Do not suppress type problems merely to satisfy tools.

## 3. Architecture boundaries

- Domain code must never call WordPress or WooCommerce functions.
- Domain code must never register or invoke hooks.
- Hooks belong inside `WordPress/`.
- WordPress option access belongs behind the settings repository.
- WordPress HTTP access belongs behind the HTTP client abstraction.
- Providers must not know forms.
- Forms must not contain provider-specific branches.
- WordPress and WooCommerce bootstrap responsibilities must remain separate.
- Classic Checkout and Checkout Block must remain separate integration adapters around shared checkout configuration.

## 4. WordPress boundaries

- Hook callbacks must stay thin.
- Normalize request data before passing it inward.
- Sanitize input and escape output at the correct boundary.
- Do not perform provider HTTP requests directly from hook callbacks.
- Do not persist settings directly from rendering classes.
- Load admin assets only on the WP Captcha Shield settings page.
- WooCommerce-specific initialization must remain inactive when WooCommerce is unavailable.

## 5. Admin UI organization

- `SettingsPage` coordinates WordPress page behaviour and should remain small.
- Page layout belongs in `SettingsPageView`.
- Shared field output belongs in `SettingsFieldRenderer`.
- Individual settings tabs implement `SettingsTabSection`.
- Provider tabs belong in focused section classes.
- Environment compatibility rules belong in Domain code rather than UI rendering code.
- Do not move unrelated business rules into admin view classes for convenience.

## 6. Naming and organization

- Classes: `PascalCase`
- Methods and variables: `camelCase`
- Constants: uppercase snake case
- Avoid vague names such as `Helper`, `Manager`, `Common`, `Utility`, or `Misc`.
- One class should have one clear responsibility.
- Files should contain one primary class, interface, enum, or trait.
- Names should express architectural responsibility rather than implementation convenience.

## 7. Internationalization and output safety

- Always pass the `wp-captcha-shield` text domain explicitly.
- Use the translation function appropriate to the context.
- Translation does not replace escaping.
- Escape HTML, attributes, URLs, and JavaScript values for their final context.
- Never expose raw provider errors to visitors.
- Add translator comments immediately before translatable strings when placeholders or ambiguous context require explanation.
- Keep `languages/wp-captcha-shield.pot` generated from production source strings rather than editing extracted entries manually.
- Exclude generated and development-only directories such as `build`, `vendor`, `tests`, and `coverage` from POT scanning.

## 8. Error handling

- Map provider failures into the common verification result.
- Do not leak stack traces, exceptions, payloads, tokens, or credentials.
- Do not catch exceptions without handling, mapping, or deliberately rethrowing them.
- Use explicit results for expected verification outcomes.
- Reserve exceptions for unexpected technical failures.
- Protected actions must fail closed when required verification cannot be completed.

## 9. Frontend JavaScript

- Keep form-integration JavaScript narrowly scoped to the platform lifecycle it supports.
- Do not duplicate provider-independent PHP decisions in JavaScript.
- Provider-specific API calls may exist in rendering/rehydration scripts, but form integrations must remain provider-neutral.
- Guard against duplicate initialization when provider APIs or WooCommerce lifecycle events can fire repeatedly.
- Executable CAPTCHA modes must prevent submission races while a token is being obtained.
- Classic Checkout widgets must tolerate WooCommerce AJAX re-rendering.
- Checkout Block token transfer must use WooCommerce extension data rather than bypassing the Store API contract.
- Production frontend assets must be minified for release builds.

## 10. Test-first workflow

For new behavior:

1. Define the behavior and edge cases.
2. Add or update the smallest relevant test.
3. Implement the minimum required code.
4. Refactor while tests remain green.
5. Run the complete relevant checks.

A test-first mindset is required; ceremony is not.

## 11. PHPUnit

- PHPUnit is the primary PHP test runner.
- Tests must be deterministic and independent.
- Tests must not depend on execution order.
- Test names must describe behavior and outcome.
- Each test should focus on one behavior.
- Use data providers when they improve clarity.
- Cover success, failure, edge cases, and regressions where relevant.
- Domain tests must run without WordPress.
- Provider tests must not call live services.
- Classic and Block checkout tests must cover their distinct platform boundaries.

## 12. Mockery

- Use Mockery only for genuine architectural interfaces, external systems, or infrastructure boundaries.
- Keep production design independent of testing-library limitations.
- Do not introduce interfaces, remove `final`, alter visibility, or change production architecture solely to make tests or mocking tools easier.
- In tests, use real final collaborators where they are normal production collaborators.
- Do not introduce an interface solely to enable mocking.
- Prefer simple fakes when clearer.
- Never mock the class under test.
- Avoid over-specifying internal calls.
- Mock only contract-relevant interactions.
- Close Mockery through its PHPUnit integration.

## 13. Brain Monkey

- Use Brain Monkey for isolated WordPress function, action, and filter tests.
- Do not use Brain Monkey in Domain tests.
- Brain Monkey does not replace real WordPress integration tests.
- Keep WordPress-specific tests in the WordPress test area.

## 14. Integration and E2E testing

- Integration tests verify important WordPress and WooCommerce boundaries.
- Playwright tests browser-based critical user journeys.
- Automated E2E tests must not solve live CAPTCHAs.
- Provider outcomes must use deterministic test seams.
- Live Cloudflare, Google, and hCaptcha verification is tested manually.
- hCaptcha Invisible release validation must use a real hostname.
- E2E tests should not duplicate every unit test.

## 15. Code coverage

Tools and reporting:

- PHPUnit generates coverage reports.
- PCOV is used for coverage collection in GitHub Actions.
- Codecov receives the generated Clover XML report.
- Production code under `src/` is included in coverage measurement.
- Generated coverage output belongs under `coverage/`.
- Generated coverage files must not be committed.

Local coverage is run through:

```text
composer test:coverage
```

The command must generate:

```text
coverage/clover.xml
```

GitHub Actions coverage rules:

- Coverage is collected in one dedicated job.
- The coverage job runs on PHP 8.1, the minimum supported PHP version.
- Compatibility matrix jobs run without duplicate coverage instrumentation.
- Coverage generation or upload failures must not be silently ignored.
- Tokens and credentials must never be committed to the repository.

Coverage interpretation:

- Coverage is evidence that code executed during tests; it is not proof that behaviour is correct.
- Do not add low-value tests merely to increase a percentage.
- Do not test private implementation details solely to increase coverage.
- Do not weaken encapsulation, remove `final`, alter visibility, or introduce interfaces solely for coverage.
- Untested branches involving failures, malformed responses, provider errors, and security-sensitive behaviour must be reviewed deliberately.
- Excluding production code from coverage requires a documented technical reason.
- Coverage does not replace architecture review, PHPStan, PHPCS, integration tests, E2E tests, or manual provider verification.

## 16. Static analysis

Required:

- `phpstan/phpstan`
- `phpstan/phpstan-strict-rules`
- `szepeviktor/phpstan-wordpress`

Rules:

- Production code must pass the configured PHPStan level.
- Do not add baseline entries merely to silence errors.
- Ignore rules require a documented reason.
- Fix the underlying type problem whenever practical.
- Configure WordPress support correctly instead of weakening analysis.

## 17. Coding-standard enforcement

Required:

- `squizlabs/php_codesniffer`
- `wp-coding-standards/wpcs`
- `slevomat/coding-standard`

Rules:

- Maintain one project PHPCS ruleset.
- WPCS owns WordPress-specific security, escaping, sanitization, and compatibility.
- Slevomat owns modern PHP structure, namespaces, types, and maintainability where compatible.
- Resolve conflicts deliberately.
- Do not enable every rule blindly.
- Disabling a rule requires a clear reason.

## 18. Quality commands

Composer exposes:

```text
composer test
composer test:unit
composer test:integration
composer test:coverage
composer analyse
composer lint
composer fix
composer check
```

Expected responsibilities:

- `test`: complete PHPUnit test suite
- `test:unit`: isolated unit test suite
- `test:integration`: integration test suite
- `test:coverage`: unit tests with Clover coverage output
- `analyse`: PHPStan
- `lint`: PHPCS
- `fix`: automatically fix supported coding-standard violations
- `check`: all required PHP quality checks

The regular `check` command does not generate or upload coverage.

Node tooling may run Playwright, frontend checks, asset builds, and minification.

## 19. Documentation synchronization

Documentation must be updated when implementation changes affect:

- supported forms;
- supported provider modes;
- minimum supported versions;
- architecture or dependency composition;
- settings behaviour;
- admin UI structure;
- build, packaging, localization, or release workflow;
- testing requirements.

Documentation priority is:

1. `ARCHITECTURE.md`
2. `TECHNICAL_REQUIREMENTS.md`
3. `CODING_STANDARDS.md`
4. `README.md`

Do not leave lower-priority documents describing behaviour that has already been superseded by implementation and higher-priority project decisions.

## 20. Completion criteria

A change is complete only when:

- relevant tests pass;
- PHPStan passes;
- PHPCS passes;
- architecture boundaries remain intact;
- no unrelated files were changed;
- strings are translatable;
- translator context is provided where placeholders require it;
- input and output handling meet WordPress security requirements;
- relevant changed production code has meaningful test coverage;
- uncovered changed lines have been reviewed and justified where necessary;
- production assets meet release-build requirements;
- documentation is updated when behavior, configuration, tooling, architecture, localization, or development workflow changes.
