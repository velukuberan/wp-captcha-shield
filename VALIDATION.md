# Validation

Performed locally on the generated WCS-025 files:

- PHP syntax check for every generated production file.
- Standalone behavioural validation for defaults, malformed stored data, provider and form parsing, save round-trip, and runtime configuration mapping.

Result: passed.

`composer check` was not run because this execution environment cannot resolve GitHub/Packagist and the repository could not be cloned with its Composer dependencies.
