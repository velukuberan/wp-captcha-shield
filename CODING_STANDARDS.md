# Coding Standards

## 1. General principles

- Keep modules small, cohesive, and independently testable.
- A small change should require understanding only the relevant module and its contract.
- Follow SOLID without creating abstractions for appearance.
- Preserve the `Domain/`, `Providers/`, and `WordPress/` boundaries.
- Prefer explicit dependencies over globals, service locators, and hidden state.
- Do not mix unrelated refactoring with feature work.

## 2. PHP and project structure

- PHP 8.1 or newer is required.
- Use namespaces and PSR-4 autoloading.
- Composer is the project autoloader.
- Keep the main plugin file as a thin bootstrap.
- Use strict types where practical.
- Prefer typed properties, parameters, and return types.
- Keep public APIs small and intentional.
- Do not suppress type problems merely to satisfy tools.

## 3. WordPress boundaries

- Domain code must never call WordPress or WooCommerce functions.
- Domain code must never register or invoke hooks.
- Hooks belong inside `WordPress/`.
- WordPress option access belongs behind the repository.
- WordPress HTTP access belongs behind the HTTP client abstraction.
- Hook callbacks must stay thin.
- Normalize request data before passing it inward.
- Sanitize input and escape output at the correct boundary.

## 4. Naming and organization

- Classes: `PascalCase`
- Methods and variables: `camelCase`
- Constants: uppercase snake case
- Avoid vague names such as `Helper`, `Manager`, `Common`, `Utility`, or `Misc`.
- One class should have one clear responsibility.
- Files should contain one primary class, interface, enum, or trait.
- Providers must not know forms.
- Forms must not contain provider-specific branches.

## 5. Internationalization and output safety

- Always pass the plugin text domain explicitly.
- Use the translation function appropriate to the context.
- Translation does not replace escaping.
- Escape HTML, attributes, URLs, and JavaScript values for their final context.
- Never expose raw provider errors to visitors.

## 6. Error handling

- Map provider failures into the common verification result.
- Do not leak stack traces, exceptions, payloads, tokens, or credentials.
- Do not catch exceptions without handling, mapping, or deliberately rethrowing them.
- Use explicit results for expected verification outcomes.
- Reserve exceptions for unexpected technical failures.

## 7. Test-first workflow

For new behavior:

1. Define the behavior and edge cases.
2. Add or update the smallest relevant test.
3. Implement the minimum required code.
4. Refactor while tests remain green.
5. Run the complete relevant checks.

A test-first mindset is required; ceremony is not.

## 8. PHPUnit

- PHPUnit is the primary PHP test runner.
- Tests must be deterministic and independent.
- Tests must not depend on execution order.
- Test names must describe behavior and outcome.
- Each test should focus on one behavior.
- Use data providers when they improve clarity.
- Cover success, failure, edge cases, and regressions where relevant.
- Domain tests must run without WordPress.
- Provider tests must not call live services.

## 9. Mockery

- Use Mockery for collaborators and external boundaries.
- Keep production design independent of testing-library limitations.
- Do not introduce interfaces, remove `final`, alter visibility, or change production architecture solely to make tests or mocking tools easier.
- In tests, use real final collaborators and mock only genuine architectural interfaces, external systems, or infrastructure boundaries.
- Mock genuine interfaces and external boundaries; do not introduce an interface solely to enable mocking.
- Prefer simple fakes when clearer.
- Never mock the class under test.
- Avoid over-specifying internal calls.
- Mock only contract-relevant interactions.
- Close Mockery through its PHPUnit integration.

## 10. Brain Monkey

- Use Brain Monkey for isolated WordPress function, action, and filter tests.
- Do not use Brain Monkey in domain tests.
- Brain Monkey does not replace real WordPress integration tests.
- Keep WordPress-specific tests in the WordPress test area.

## 11. Integration and E2E testing

- Integration tests verify important WordPress and WooCommerce boundaries.
- Playwright tests browser-based critical user journeys.
- Automated E2E tests must not solve live CAPTCHAs.
- Provider outcomes must use deterministic test seams.
- Live Cloudflare, Google, and hCaptcha verification is tested manually.
- E2E tests should not duplicate every unit test.

## 12. Static analysis

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

## 13. Coding-standard enforcement

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

## 14. Quality commands

Composer should expose:

```text
composer test
composer analyse
composer lint
composer check
```

Expected responsibilities:

- `test`: PHPUnit
- `analyse`: PHPStan
- `lint`: PHPCS
- `check`: all required PHP quality checks

Node scripts may run Playwright and frontend checks.

## 15. Completion criteria

A change is complete only when:

- relevant tests pass;
- PHPStan passes;
- PHPCS passes;
- architecture boundaries remain intact;
- no unrelated files were changed;
- strings are translatable;
- input and output handling meet WordPress security requirements;
- documentation is updated when behavior or configuration changes.
