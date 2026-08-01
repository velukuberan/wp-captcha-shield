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

## 12. Code coverage

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
- The PHP 8.1, 8.2, and 8.3 compatibility matrix runs without coverage instrumentation.
- Coverage must not be generated independently by every PHP matrix job.
- A failed coverage generation or Codecov upload fails the dedicated coverage job.
- The Codecov token must be stored as the `CODECOV_TOKEN` GitHub Actions secret.
- Tokens and credentials must never be committed to the repository.

Codecov status rules:

- Project coverage measures the complete measured codebase.
- Project coverage is initially informational and does not block pull requests.
- Patch coverage measures production lines changed by a pull request.
- The initial patch-coverage target is 80%.
- The initial patch-coverage threshold is 5%.
- Coverage targets may be adjusted deliberately as the codebase and test suite mature.
- Coverage configuration changes must not silently weaken existing expectations.

Coverage interpretation:

- Coverage is evidence that code executed during tests; it is not proof that behaviour is correct.
- Do not add low-value tests merely to increase a percentage.
- Do not test private implementation details solely to increase coverage.
- Do not weaken encapsulation, remove `final`, alter visibility, or introduce interfaces solely for coverage.
- Untested branches involving failures, malformed responses, provider errors, and security-sensitive behaviour must be reviewed deliberately.
- Excluding production code from coverage requires a documented technical reason.
- Coverage does not replace architecture review, PHPStan, PHPCS, integration tests, E2E tests, or manual provider verification.

For new and changed behaviour:

- Add tests for meaningful success, failure, and edge-case paths.
- Review Codecov patch coverage on the pull request.
- Investigate uncovered changed lines rather than accepting the percentage without review.
- When a changed line is intentionally untested, document the reason in the pull request.

## 13. Static analysis

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

## 14. Coding-standard enforcement

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

## 15. Quality commands

Composer should expose:

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

The regular `check` command does not need to generate or upload coverage. Coverage runs separately because instrumentation adds execution overhead and Codecov upload belongs to CI rather than the normal local quality command.

Node scripts may run Playwright and frontend checks.

## 16. Completion criteria

A change is complete only when:

- relevant tests pass;
- PHPStan passes;
- PHPCS passes;
- architecture boundaries remain intact;
- no unrelated files were changed;
- strings are translatable;
- input and output handling meet WordPress security requirements;
- relevant changed production code has meaningful test coverage;
- uncovered changed lines have been reviewed and justified where necessary;
- documentation is updated when behavior, configuration, tooling, or development workflow changes.
