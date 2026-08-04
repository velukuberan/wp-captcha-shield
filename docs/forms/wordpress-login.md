# Protect the WordPress login form

WordPress login is the currently implemented form integration.

## Use the global default

In **Settings → WP Captcha Shield**:

1. select the desired **Default provider**;
2. set **WordPress login** to **Use default**;
3. configure the selected provider's credentials and mode;
4. save the settings.

## Use a form-specific provider

You can keep the global default disabled or set it to another provider, then
select a specific provider for **WordPress login**.

Available values:

- Use default
- Disabled
- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha

## Test the form

Use a private browser session:

1. open the WordPress login page;
2. confirm the selected CAPTCHA integration loads;
3. submit valid credentials;
4. confirm successful verification allows login;
5. confirm a failed or unavailable verification rejects the protected action.

Protected forms fail closed when verification cannot be completed.
