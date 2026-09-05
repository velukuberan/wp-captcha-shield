# Configure Google reCAPTCHA Invisible

This guide explains how to create a Google reCAPTCHA Enterprise **Invisible** web key with the Google Cloud CLI and connect it to WP Captcha Shield.

Invisible mode does not show the "I'm not a robot" checkbox. Google may still present a CAPTCHA challenge after risk analysis.

## Before you begin

You need:

- a Google account;
- a Google Cloud project;
- the Google Cloud CLI (`gcloud`) installed;
- permission to create reCAPTCHA keys in the project;
- WP Captcha Shield installed;
- the hostname where reCAPTCHA will run.

Use separate keys for local development, staging, and production. Do not add local development hostnames to a production key.

## 1. Authenticate and select the project

Sign in to Google Cloud:

```bash
gcloud auth login
```

Select the same project that will be entered in WP Captcha Shield:

```bash
gcloud config set project PROJECT_ID
```

Confirm the active project:

```bash
gcloud config get-value project
```

Enable the reCAPTCHA Enterprise API:

```bash
gcloud services enable recaptchaenterprise.googleapis.com
```

Replace `PROJECT_ID` with the Google Cloud project ID, not the project display name or numeric project number.

## 2. Create an Invisible key for local development

Create a key restricted to `localhost`:

```bash
gcloud recaptcha keys create \
  --display-name="WP Captcha Shield Invisible - Local" \
  --web \
  --integration-type=invisible \
  --domains=localhost
```

The important option is:

```text
--integration-type=invisible
```

It creates a Web key that does not display the checkbox but may show a CAPTCHA challenge after Google's risk analysis.

## 3. Create an Invisible key for production

Create a separate production key with the permitted hostnames:

```bash
gcloud recaptcha keys create \
  --display-name="WP Captcha Shield Invisible - Production" \
  --web \
  --integration-type=invisible \
  --domains=example.com,www.example.com
```

Replace the example domains with the website's real hostnames.

Valid values contain only a hostname or subdomain:

```text
example.com
www.example.com
staging.example.com
localhost
```

Do not include a scheme, path, port, query, or fragment.

Incorrect values include:

```text
https://example.com
example.com/wp-login.php
example.com:443
```

All subdomains of an allowed domain are automatically allowed by Google. Add explicit subdomains when you want the key configuration to document the intended hosts clearly.

## 4. Find the generated site key

The create command returns the created reCAPTCHA key resource. The key ID is the **Site key** required by WP Captcha Shield.

List keys in the active project:

```bash
gcloud recaptcha keys list
```

Describe a particular key:

```bash
gcloud recaptcha keys describe SITE_KEY
```

For a focused view of its display name and Web settings:

```bash
gcloud recaptcha keys describe SITE_KEY \
  --format="yaml(displayName,webSettings)"
```

Confirm that the output identifies an Invisible Web key and includes the expected allowed domains.

## 5. Understand the required credentials

WP Captcha Shield requires three Google values:

| Plugin field | Google source |
|---|---|
| Project ID | Google Cloud project ID |
| API key | APIs & Services → Credentials |
| Site key | Key ID created by `gcloud recaptcha keys create` |

Google may also display a reCAPTCHA **Secret key**, but the current plugin does not use it.

```text
Site key   = reCAPTCHA key ID created by gcloud
API key    = Google Cloud API credential
Secret key = not used by WP Captcha Shield
```

Do not paste the reCAPTCHA Secret key into the plugin's API-key field.

## 6. Configure WP Captcha Shield

In WordPress administration, open:

```text
Settings → WP Captcha Shield
```

Under **General settings**, select Google reCAPTCHA as the default provider or select Google reCAPTCHA directly for the protected form.

In the **Google reCAPTCHA** section, configure:

```text
Project ID:       Google Cloud project ID
API key:          Server-side Google Cloud API key
Site key:         Key ID created by gcloud
Mode:             Invisible
Minimum score:    0.5
```

Save the settings.

The Google key type and plugin mode must match:

```text
--integration-type=invisible
Mode: Invisible
```

Selecting **Invisible** only in WP Captcha Shield does not convert a score-based or checkbox key into an Invisible key.

After the API key is saved, the plugin does not display it again. Leave the API-key field blank during later saves to preserve the stored value.

## 7. Test Invisible mode

Use a private or incognito browser session.

1. Open the protected WordPress login page.
2. Confirm that no checkbox is displayed.
3. Submit valid login credentials.
4. Confirm that the protected form resumes after Google returns a token.
5. Confirm that login succeeds after successful server-side verification.
6. Test a failed, expired, reused, or rejected token condition.
7. Confirm that the protected action fails closed instead of bypassing verification.

At runtime:

1. WP Captcha Shield renders an Invisible Google widget.
2. The visitor submits the protected form.
3. The plugin executes the widget.
4. Google returns a token.
5. The form resumes with that token.
6. WordPress sends the token to the plugin's server-side verifier.
7. The verifier creates a reCAPTCHA Enterprise assessment.
8. The action continues only when verification succeeds.

Tokens are untrusted, single-use, short-lived values and are not stored by WP Captcha Shield.

## 8. Create deterministic testing keys

Testing keys are for development and staging only. Never use them in production.

### Always return no challenge

```bash
gcloud recaptcha keys create \
  --display-name="WP Captcha Shield Invisible - No Challenge Test" \
  --web \
  --integration-type=invisible \
  --domains=localhost \
  --testing-challenge=nocaptcha \
  --testing-score=0.9
```

This key always returns no CAPTCHA challenge and a score of `0.9`.

### Always return a testing challenge

```bash
gcloud recaptcha keys create \
  --display-name="WP Captcha Shield Invisible - Challenge Test" \
  --web \
  --integration-type=invisible \
  --domains=localhost \
  --testing-challenge=challenge
```

Google intentionally makes the forced testing challenge unsolvable. Use it to verify that the challenge or failure path blocks the protected action correctly.

## 9. Update allowed domains

Update the domain list of an existing Web key:

```bash
gcloud recaptcha keys update SITE_KEY \
  --web \
  --domains=example.com,www.example.com
```

This replaces the key's allowed-domain configuration with the supplied list.

The update command does not provide an option to change the integration type. To move between Score-based, Checkbox, and Invisible modes, create a new key and update the Site key and Mode in WP Captcha Shield.

## 10. Delete temporary keys

Delete a temporary or obsolete key:

```bash
gcloud recaptcha keys delete SITE_KEY
```

Verify that the key is no longer listed:

```bash
gcloud recaptcha keys list
```

Do not delete an active production key until its replacement has been configured and tested.

## Troubleshooting

### `gcloud: command not found`

Install the Google Cloud CLI and reopen the terminal before running the commands.

### The command uses the wrong project

Check the active project:

```bash
gcloud config get-value project
```

Change it when necessary:

```bash
gcloud config set project PROJECT_ID
```

The active project must match the Project ID configured in WP Captcha Shield.

### The reCAPTCHA API is unavailable

Enable it in the active project:

```bash
gcloud services enable recaptchaenterprise.googleapis.com
```

Also confirm that the signed-in account has permission to create and manage reCAPTCHA keys.

### The browser hostname is rejected

Describe the key and inspect its Web settings:

```bash
gcloud recaptcha keys describe SITE_KEY \
  --format="yaml(displayName,webSettings)"
```

Add the correct hostname with `gcloud recaptcha keys update`. Do not include `https://`, a path, or a port.

### The key was created as Score-based or Checkbox

Run:

```bash
gcloud recaptcha keys describe SITE_KEY
```

If it is not an Invisible integration, create a new key using:

```text
--integration-type=invisible
```

Then replace the Site key in WP Captcha Shield and keep **Mode: Invisible** selected.

### The widget or badge does not load

Check that:

- the browser hostname is allowed by the key;
- the Site key is correct;
- the plugin mode is **Invisible**;
- another plugin is not blocking Google's script;
- the site's Content Security Policy permits the required Google resources;
- a browser privacy or content-blocking extension is not blocking the script.

### Form submission does not resume

Check the browser console for script errors and confirm that the Google script loaded successfully. Retry with extensions disabled and confirm that the protected form contains the Invisible widget and token field rendered by WP Captcha Shield.

### Login fails after waiting on the page

A reCAPTCHA token is short-lived and can be assessed only once. Reload or resubmit the form so the browser can generate a new token.

### The API key was confused with the Site key

The Site key is the reCAPTCHA key ID created by `gcloud recaptcha keys create`. The API key is a separate Google Cloud credential used by the WordPress server.

### The forced testing challenge cannot be solved

This is expected for a key created with:

```text
--testing-challenge=challenge
```

Google intentionally returns an unsolvable testing challenge. Use a `nocaptcha` testing key or a normal development key when testing the successful path.

## Related documentation

- [General Google reCAPTCHA setup](google-recaptcha.md)
- [Provider setup overview](index.md)

## Official Google references

- [Create a reCAPTCHA key with gcloud](https://cloud.google.com/sdk/gcloud/reference/recaptcha/keys/create)
- [List reCAPTCHA keys](https://cloud.google.com/sdk/gcloud/reference/recaptcha/keys/list)
- [Describe a reCAPTCHA key](https://cloud.google.com/sdk/gcloud/reference/recaptcha/keys/describe)
- [Update a reCAPTCHA key](https://cloud.google.com/sdk/gcloud/reference/recaptcha/keys/update)
- [Delete a reCAPTCHA key](https://cloud.google.com/sdk/gcloud/reference/recaptcha/keys/delete)
