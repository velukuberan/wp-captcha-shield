---
title: WP Captcha Shield documentation
---

<section class="hero">
  <div class="hero__glow" aria-hidden="true"></div>
  <div class="hero__inner">
    <span class="status-pill"><i></i> Open-source WordPress CAPTCHA protection</span>

    <h1>Calm, configurable CAPTCHA protection for WordPress.</h1>

    <p class="hero__lead">
      Protect selected WordPress and WooCommerce forms with Cloudflare
      Turnstile, Google reCAPTCHA, or hCaptcha—without coupling form
      integrations to individual providers.
    </p>

    <div class="hero__actions">
      <a class="button button--primary" href="getting-started/">Get started <span>→</span></a>
      <a class="button button--secondary" href="providers/cloudflare-turnstile/">Configure Turnstile</a>
    </div>

    <div class="tag-row" aria-label="Highlights">
      <span>Provider neutral</span>
      <span>Server-side verification</span>
      <span>Per-form overrides</span>
      <span>Fail closed</span>
    </div>
  </div>
</section>

<section class="home-section home-section--intro">
  <div class="section-heading">
    <span class="eyebrow">Configuration model</span>
    <h2>Simple choices, clear responsibilities.</h2>
  </div>

  <div class="section-copy">
    <p>
      Choose one global default CAPTCHA provider, then override or disable
      protection for individual forms. Provider credentials and modes remain
      independent from form selection.
    </p>

    <div class="mini-card-grid">
      <article class="mini-card">
        <h3>Global default</h3>
        <p>Choose the provider most forms should inherit.</p>
      </article>
      <article class="mini-card">
        <h3>Per-form control</h3>
        <p>Use the default, select another provider, or disable CAPTCHA.</p>
      </article>
      <article class="mini-card">
        <h3>Provider isolation</h3>
        <p>Forms do not contain provider-specific implementation branches.</p>
      </article>
    </div>
  </div>
</section>

<section class="home-section home-section--providers">
  <div class="section-heading section-heading--wide">
    <span class="eyebrow">Provider setup</span>
    <h2>Three providers, one consistent integration model.</h2>
  </div>

  <div class="feature-card-grid">
    <a class="feature-card feature-card--accent" href="providers/cloudflare-turnstile/">
      <span class="feature-card__icon">◇</span>
      <h3>Cloudflare Turnstile</h3>
      <p>Configure Managed, Non-Interactive, or Invisible mode with a complete screenshot guide.</p>
      <span class="feature-card__meta">Guide available · Open setup →</span>
    </a>

    <a class="feature-card" href="providers/google-recaptcha/">
      <span class="feature-card__icon">⌁</span>
      <h3>Google reCAPTCHA</h3>
      <p>Score-based, checkbox, and invisible protection through reCAPTCHA Enterprise.</p>
      <span class="feature-card__meta">Open setup →</span>
    </a>

    <a class="feature-card" href="providers/hcaptcha/">
      <span class="feature-card__icon">⬡</span>
      <h3>hCaptcha</h3>
      <p>Checkbox and invisible display modes with server-side token verification.</p>
      <span class="feature-card__meta">Open setup →</span>
    </a>
  </div>
</section>

<section class="home-section home-section--capabilities">
  <div class="section-heading section-heading--wide">
    <span class="eyebrow">Protection foundations</span>
    <h2>Built around durable boundaries.</h2>
  </div>

  <div class="capability-grid">
    <article>
      <span>01</span>
      <h3>Server-side verification</h3>
      <p>Submitted tokens are treated as untrusted input and verified through provider APIs.</p>
    </article>
    <article>
      <span>02</span>
      <h3>Provider-neutral forms</h3>
      <p>WordPress integrations depend on shared contracts, not provider branches.</p>
    </article>
    <article>
      <span>03</span>
      <h3>Fail-closed behavior</h3>
      <p>Protected actions are rejected when verification cannot be completed.</p>
    </article>
    <article>
      <span>04</span>
      <h3>Private credentials</h3>
      <p>Provider secrets remain server-side and are never rendered into public HTML.</p>
    </article>
  </div>
</section>

<section class="home-section home-section--status">
  <div class="status-panel">
    <div>
      <span class="eyebrow">Project status</span>
      <h2>Release ready.</h2>
      <p>
        Provider integrations and supported WordPress and WooCommerce form
        integrations are implemented and ready for configuration.
      </p>
    </div>
    <a class="button button--secondary" href="forms/">View form setup →</a>
  </div>
</section>
