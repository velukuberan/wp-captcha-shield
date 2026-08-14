(function () {
  if (typeof jQuery === "undefined") return;

  function mountTurnstile() {
    if (!window.turnstile) return;
    document.querySelectorAll(".cf-turnstile").forEach(function (el) {
      if (el.dataset.wpcsRendered === "1") return;
      window.turnstile.render(el, {
        sitekey: el.getAttribute("data-sitekey"),
        action: el.getAttribute("data-action") || undefined,
        size: el.getAttribute("data-size") || undefined,
      });
      el.dataset.wpcsRendered = "1";
    });
  }

  function mountHCaptcha() {
    if (!window.hcaptcha) return;
    document.querySelectorAll(".h-captcha").forEach(function (el) {
      if (el.dataset.wpcsRendered === "1") return;
      if (el.querySelector("iframe")) {
        el.dataset.wpcsRendered = "1";
        return;
      }
      window.hcaptcha.render(el, { sitekey: el.getAttribute("data-sitekey") });
      el.dataset.wpcsRendered = "1";
    });
  }

  function mountRecaptcha() {
    var api = window.grecaptcha && window.grecaptcha.enterprise;
    if (!api) return;
    document.querySelectorAll(".g-recaptcha").forEach(function (el) {
      if (el.dataset.wpcsRendered === "1") return;
      if (el.querySelector("iframe")) {
        el.dataset.wpcsRendered = "1";
        return;
      }
      api.render(el, {
        sitekey: el.getAttribute("data-sitekey"),
        action: el.getAttribute("data-action") || undefined,
      });
      el.dataset.wpcsRendered = "1";
    });
  }

  jQuery(document.body).on("updated_checkout", function () {
    mountTurnstile();
    mountHCaptcha();
    mountRecaptcha();
  });
})();
