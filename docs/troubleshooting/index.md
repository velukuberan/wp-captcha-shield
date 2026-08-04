# Troubleshooting

## Configuration is incomplete

Confirm that all required provider credentials are saved.

- Turnstile requires a site key and secret key.
- Google reCAPTCHA requires a project ID, API key, and site key.
- hCaptcha requires a site key and secret key.

## The wrong provider appears

Check both:

- **Default provider**
- the individual form override

A form-specific selection takes precedence over the global default.

## The widget does not load

Check:

- the provider hostname or domain configuration;
- that the correct site key was copied;
- that the plugin mode matches the provider-dashboard mode;
- browser console errors;
- Content Security Policy restrictions;
- conflicts with optimization or security plugins.

## Verification fails

Confirm:

- the secret or API credential is valid;
- the token has not expired;
- the hostname matches;
- outbound HTTP requests are allowed;
- the provider service is available.

WP Captcha Shield maps expected outcomes to Successful, Failed, Unavailable, or
Misconfigured and does not expose raw provider responses to visitors.
