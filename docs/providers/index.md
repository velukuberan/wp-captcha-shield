# Provider setup

WP Captcha Shield currently implements three providers.

| Provider | Modes | Guide |
|---|---|---|
| Cloudflare Turnstile | Managed, Non-Interactive, Invisible | [Available](cloudflare-turnstile.md) |
| Google reCAPTCHA | Score-based, Checkbox, Invisible | [General setup](google-recaptcha.md) · [Invisible setup](google-recaptcha-invisible.md) |
| hCaptcha | Checkbox, Invisible | [In preparation](hcaptcha.md) |

Provider credentials are configured independently. Selecting a provider for a
form does not change the mode or credentials configured in that provider's
dashboard.
