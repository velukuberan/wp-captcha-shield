(function () {
  "use strict";

  var WIDGET_SELECTOR = ".wp-captcha-shield-google-invisible-widget";
  var REQUEST_TOKEN_EVENT = "wp-captcha-shield:request-token";

  function initialize(container) {
    if (container.dataset.initialized === "true") {
      return;
    }

    var formId = container.dataset.formId;
    var tokenId = container.dataset.tokenId;
    var siteKey = container.dataset.siteKey;
    var action = container.dataset.action;
    var form = findForm(container, formId);
    var blockRoot = container.closest("[data-wp-captcha-shield-block-checkout]");
    var tokenField = findTokenField(container, tokenId);
    var widgetId = null;
    var requestingToken = false;
    var submitter = null;
    var checkoutCompletion = null;
    var checkoutFailure = null;

    if ((!form && !blockRoot) || !tokenField || !siteKey || !action) {
      return;
    }

    try {
      widgetId = grecaptcha.enterprise.render(container, {
        sitekey: siteKey,
        size: "invisible",
        action: action,
        callback: function (token) {
          tokenField.value = token;
          requestingToken = false;

          if (checkoutCompletion) {
            var completion = checkoutCompletion;

            checkoutCompletion = null;
            checkoutFailure = null;
            completion(token);
            return;
          }

          if (form) {
            resumeSubmission(form, submitter);
          }
        },
        "expired-callback": function () {
          tokenField.value = "";
          requestingToken = false;
          failCheckoutRequest();
        },
        "error-callback": function () {
          tokenField.value = "";
          requestingToken = false;
          failCheckoutRequest();
        },
      });

      container.dataset.initialized = "true";
    } catch (error) {
      widgetId = null;
      return;
    }

    function failCheckoutRequest() {
      if (!checkoutFailure) {
        return;
      }

      var failure = checkoutFailure;

      checkoutCompletion = null;
      checkoutFailure = null;
      failure();
    }

    function execute() {
      if (requestingToken || widgetId === null) {
        return false;
      }

      requestingToken = true;

      grecaptcha.enterprise.execute(widgetId).catch(function () {
        requestingToken = false;
        tokenField.value = "";
        grecaptcha.enterprise.reset(widgetId);
        failCheckoutRequest();
      });

      return true;
    }

    if (form) {
      form.addEventListener("submit", function (event) {
        if (tokenField.value !== "") {
          return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        submitter = event.submitter || null;
        execute();
      }, true);
    }

    document.addEventListener(REQUEST_TOKEN_EVENT, function (event) {
      var detail = event.detail;

      if (!detail || !detail.root || !detail.root.contains(container)) {
        return;
      }

      detail.handled = true;
      tokenField.value = "";
      checkoutCompletion = detail.complete;
      checkoutFailure = detail.fail;

      if (!execute()) {
        failCheckoutRequest();
      }
    });
  }

  function findForm(element, formId) {
    if (formId) {
      var configuredForm = document.getElementById(formId);

      if (configuredForm) {
        return configuredForm;
      }
    }

    return element.closest("form");
  }

  function findTokenField(container, tokenId) {
    if (tokenId) {
      var configuredTokenField = document.getElementById(tokenId);

      if (configuredTokenField) {
        return configuredTokenField;
      }
    }

    var form = container.closest("form");

    if (!form) {
      return null;
    }

    return form.querySelector('input[name="wp_captcha_shield_google_token"]');
  }

  function googleIsAvailable() {
    return (
      typeof grecaptcha !== "undefined" &&
      typeof grecaptcha.enterprise !== "undefined" &&
      typeof grecaptcha.enterprise.ready === "function" &&
      typeof grecaptcha.enterprise.render === "function" &&
      typeof grecaptcha.enterprise.execute === "function" &&
      typeof grecaptcha.enterprise.reset === "function"
    );
  }

  function resumeSubmission(form, submitter) {
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

  function initializeWidgets(attempt) {
    if (!googleIsAvailable()) {
      if (attempt < 50) {
        window.setTimeout(function () {
          initializeWidgets(attempt + 1);
        }, 100);
      }

      return;
    }

    document.querySelectorAll(WIDGET_SELECTOR).forEach(initialize);
  }

  function startInitialization() {
    initializeWidgets(0);
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startInitialization);
  } else {
    startInitialization();
  }
})();
