(function () {
  if (typeof jQuery === "undefined") return;

  var REQUEST_TOKEN_EVENT = "wp-captcha-shield:request-token";

  var HCAPTCHA_INVISIBLE_SELECTOR =
    ".wp-captcha-shield-hcaptcha-invisible-widget";

  var GOOGLE_INVISIBLE_SELECTOR = ".wp-captcha-shield-google-invisible-widget";

  var GOOGLE_SCORE_SELECTOR = ".wp-captcha-shield-google-score-token";

  var requestPending = false;
  var resumeOnce = false;
  var pendingButton = null;
  var pendingButtonState = null;

  function checkoutForm() {
    return document.querySelector("form.checkout");
  }

  function executableCaptcha() {
    var hcaptcha = document.querySelector(HCAPTCHA_INVISIBLE_SELECTOR);

    if (hcaptcha) {
      return {
        root: hcaptcha,
        tokenField: "h-captcha-response",
      };
    }

    var googleInvisible = document.querySelector(GOOGLE_INVISIBLE_SELECTOR);

    if (googleInvisible) {
      return {
        root: googleInvisible,
        tokenField: "wp_captcha_shield_google_token",
      };
    }

    var googleScore = document.querySelector(GOOGLE_SCORE_SELECTOR);

    if (googleScore) {
      return {
        root: googleScore,
        tokenField: "wp_captcha_shield_google_token",
      };
    }

    return null;
  }

  function setCheckoutToken(form, fieldName, token) {
    var field = form.querySelector('[name="' + fieldName + '"]');

    if (!field) {
      field = document.createElement("input");

      field.type = "hidden";
      field.name = fieldName;

      form.appendChild(field);
    }

    field.value = token;
  }

  function setBusy(button) {
    if (!button) return;

    pendingButton = button;

    pendingButtonState = {
      opacity: button.style.opacity,

      pointerEvents: button.style.pointerEvents,

      cursor: button.style.cursor,

      ariaDisabled: button.getAttribute("aria-disabled"),

      ariaBusy: button.getAttribute("aria-busy"),
    };

    button.style.opacity = "0.6";
    button.style.pointerEvents = "none";
    button.style.cursor = "wait";

    button.setAttribute("aria-disabled", "true");
    button.setAttribute("aria-busy", "true");
  }

  function clearBusy() {
    if (!pendingButton || !pendingButtonState) {
      pendingButton = null;
      pendingButtonState = null;

      return;
    }

    pendingButton.style.opacity = pendingButtonState.opacity;

    pendingButton.style.pointerEvents = pendingButtonState.pointerEvents;

    pendingButton.style.cursor = pendingButtonState.cursor;

    if (pendingButtonState.ariaDisabled === null) {
      pendingButton.removeAttribute("aria-disabled");
    } else {
      pendingButton.setAttribute(
        "aria-disabled",
        pendingButtonState.ariaDisabled,
      );
    }

    if (pendingButtonState.ariaBusy === null) {
      pendingButton.removeAttribute("aria-busy");
    } else {
      pendingButton.setAttribute("aria-busy", pendingButtonState.ariaBusy);
    }

    pendingButton = null;
    pendingButtonState = null;
  }

  function resetRequestState() {
    requestPending = false;
    resumeOnce = false;

    clearBusy();
  }

  function resumeCheckout(form, submitter) {
    resumeOnce = true;

    /*
     * CAPTCHA processing is complete.
     * Remove our temporary busy state before handing
     * submission back to WooCommerce. WooCommerce can
     * then apply its own normal processing state.
     */
    clearBusy();

    if (typeof form.requestSubmit === "function") {
      if (submitter) {
        form.requestSubmit(submitter);

        return;
      }

      form.requestSubmit();

      return;
    }

    form.submit();
  }

  function requestCaptchaToken(form, captcha, submitter) {
    var completed = false;

    function fail() {
      if (completed) return;

      completed = true;

      resetRequestState();
    }

    function complete(token) {
      if (completed) return;

      completed = true;

      if (typeof token !== "string" || token === "") {
        resetRequestState();

        return;
      }

      setCheckoutToken(form, captcha.tokenField, token);

      requestPending = false;

      resumeCheckout(form, submitter);
    }

    var detail = {
      root: captcha.root,
      handled: false,
      complete: complete,
      fail: fail,
    };

    document.dispatchEvent(
      new CustomEvent(REQUEST_TOKEN_EVENT, {
        detail: detail,
      }),
    );

    if (!detail.handled) {
      fail();
    }
  }

  function handleCheckoutSubmit(event) {
    var captcha = executableCaptcha();

    /*
     * No supported executable CAPTCHA:
     *
     * Turnstile, visible hCaptcha,
     * visible Google reCAPTCHA and
     * ordinary WooCommerce checkout
     * continue through their existing
     * behavior untouched.
     */
    if (!captcha) {
      return;
    }

    if (resumeOnce) {
      resumeOnce = false;

      return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    if (requestPending) {
      return;
    }

    requestPending = true;

    var form = event.currentTarget;

    var submitter = event.submitter || document.querySelector("#place_order");

    setBusy(submitter);

    requestCaptchaToken(form, captcha, submitter);
  }

  function mountTurnstile() {
    if (!window.turnstile) return;

    document.querySelectorAll(".cf-turnstile").forEach(function (el) {
      if (el.dataset.wpcsRendered === "1") {
        return;
      }

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
      if (el.dataset.wpcsRendered === "1") {
        return;
      }

      if (el.querySelector("iframe")) {
        el.dataset.wpcsRendered = "1";

        return;
      }

      window.hcaptcha.render(el, {
        sitekey: el.getAttribute("data-sitekey"),
      });

      el.dataset.wpcsRendered = "1";
    });
  }

  function mountRecaptcha() {
    var api = window.grecaptcha && window.grecaptcha.enterprise;

    if (!api) return;

    document.querySelectorAll(".g-recaptcha").forEach(function (el) {
      if (el.dataset.wpcsRendered === "1") {
        return;
      }

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

  function initializeExecutableCaptcha() {
    if (typeof window.wpCaptchaShieldHCaptchaInitialize === "function") {
      window.wpCaptchaShieldHCaptchaInitialize();
    }

    if (typeof window.wpCaptchaShieldGoogleInvisibleInitialize === "function") {
      window.wpCaptchaShieldGoogleInvisibleInitialize();
    }

    if (typeof window.wpCaptchaShieldGoogleScoreInitialize === "function") {
      window.wpCaptchaShieldGoogleScoreInitialize();
    }
  }

  var form = checkoutForm();

  if (form) {
    form.addEventListener("submit", handleCheckoutSubmit, true);
  }

  jQuery(document.body).on("updated_checkout", function () {
    mountTurnstile();
    mountHCaptcha();
    mountRecaptcha();

    initializeExecutableCaptcha();
  });

  jQuery(document.body).on("checkout_error", function () {
    resetRequestState();
  });
})();
