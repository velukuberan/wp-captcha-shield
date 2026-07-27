# Architecture

## 1. Architectural stance

Simple layered design, SOLID, test-first, no full DDD.

## 2. Top-level areas

Domain/
Providers/
WordPress/

## 3. Dependency rule

WordPress and Providers may depend on Domain.
Domain must never depend on WordPress or Providers.

## 4. Configuration model

Global default:

- Disabled
- Turnstile
- reCAPTCHA
- hCaptcha

Per form:

- Use default
- Disabled
- Turnstile
- reCAPTCHA
- hCaptcha

## 5. Core boundaries

- Domain never knows hooks.
- WordPress options are accessed through a repository.
- Forms never know provider internals.
- Providers never know forms.
- Application coordination lives under WordPress.

## 6. Verification and failure

Results:

- Successful
- Failed
- Unavailable
- Misconfigured

Protected forms fail closed.
Visitors receive safe messages.
Technical details remain internal.

## 7. Extensibility

Adding a provider must not require changing form integrations.
Adding a form must not require changing providers.

## 8. Security

Server-side verification only.
Secrets remain server-side.
Tokens are untrusted and not stored.

## 9. Testing

Domain, providers, repositories, WordPress integrations, and critical end-to-end flows.

## 10. Locked invariants

A short numbered list of non-negotiable rules.
