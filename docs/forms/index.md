# Configure CAPTCHA for protected forms

WP Captcha Shield uses the same provider-selection model for all supported forms.

For each form, you can:

- use the global default provider;
- select a specific CAPTCHA provider;
- disable CAPTCHA for that form.

## Supported forms

### WordPress

- Login
- Registration
- Lost password
- Comments

### WooCommerce

- Login
- Registration
- Lost password
- Product reviews
- Classic checkout
- Checkout Block

WooCommerce protection is available only when WooCommerce is active.

## Use the global default

In **Settings → WP Captcha Shield**:

1. select the desired **Default provider**;
2. set the form you want to protect to **Use default**;
3. configure the selected provider's credentials and mode;
4. save the settings.

The form will use whichever CAPTCHA provider is selected as the global default.

## Use a form-specific provider

You can override the global default for an individual form.

For example, you can use Cloudflare Turnstile as the global default while using Google reCAPTCHA for WordPress login.

Available values for supported forms are:

- Use default
- Disabled
- Cloudflare Turnstile
- Google reCAPTCHA
- hCaptcha

## Disable CAPTCHA for a form

Select **Disabled** for any form that should not use CAPTCHA.

This overrides the global default for that form only.

## Example: protect WordPress login

To protect WordPress login using the global default:

1. configure the CAPTCHA provider you want to use;
2. select that provider as the **Default provider**;
3. set **WordPress login** to **Use default**;
4. save the settings;
5. open the WordPress login page in a private browser session;
6. confirm the selected CAPTCHA integration loads;
7. submit valid credentials and confirm login succeeds after successful verification.

To use a different provider only for WordPress login, select that provider directly for **WordPress login** instead of **Use default**.

## WooCommerce notes

### Product reviews

WooCommerce product reviews are configured separately from WordPress comments.

Changing the CAPTCHA setting for WordPress comments does not change the product-review setting.

### Checkout

Classic Checkout and Checkout Block use the same WooCommerce checkout CAPTCHA setting.

The plugin integrates with each checkout type separately, but you do not need to configure a different provider for each checkout implementation.

## Test protected forms

After changing CAPTCHA settings, test the forms that matter to your site.

For each protected form:

1. confirm the selected CAPTCHA integration loads;
2. submit the form with valid input;
3. confirm successful CAPTCHA verification allows the action;
4. confirm a failed or unavailable CAPTCHA verification rejects the protected action.

Protected forms fail closed when required verification cannot be completed.

For provider-specific setup and mode details, see the **Provider setup** section of this documentation.
