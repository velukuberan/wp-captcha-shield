(function () {
  "use strict";

  var WIDGET_SELECTOR = ".wp-captcha-shield-hcaptcha-invisible-widget";
  var REQUEST_TOKEN_EVENT = "wp-captcha-shield:request-token";

  function initialize(container) {
    var formId = container.dataset.formId;
    var siteKey = container.dataset.siteKey;
    var form = findForm(container, formId);
    var blockRoot = container.closest("[data-wp-captcha-shield-block-checkout]");
    var widgetId = null;
    var requestingToken = false;
    var submitter = null;
    var checkoutCompletion = null;
    var checkoutFailure = null;

    if ((!form && !blockRoot) || !siteKey) {
      return;
    }

    function completeCheckoutRequest(token) {
      if (!checkoutCompletion) {
        return false;
      }

      var completion = checkoutCompletion;

      checkoutCompletion = null;
      checkoutFailure = null;
      completion(token);
      return true;
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

    widgetId = hcaptcha.render(container, {
      sitekey: siteKey,
      size: "invisible",
      callback: function (token) {
        requestingToken = false;

        if (completeCheckoutRequest(token)) {
          return;
        }

        if (form) {
          resumeSubmission(form, submitter);
        }
      },
      "expired-callback": function () {
        requestingToken = false;
        failCheckoutRequest();
      },
      "error-callback": function () {
        requestingToken = false;
        failCheckoutRequest();
      },
      "close-callback": function () {
        requestingToken = false;
        failCheckoutRequest();
      },
    });

    function execute() {
      if (requestingToken || widgetId === null) {
        return false;
      }

      requestingToken = true;

      try {
        hcaptcha.execute(widgetId);
      } catch (error) {
        requestingToken = false;
        return false;
      }

      return true;
    }

    if (form) {
      form.addEventListener("submit", function (event) {
        if (widgetId !== null && hcaptcha.getResponse(widgetId) !== "") {
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

      if (requestingToken || widgetId === null) {
        detail.fail();
        return;
      }

      requestingToken = true;

      try {
        var execution = hcaptcha.execute(widgetId, { async: true });

        if (!execution || typeof execution.then !== "function") {
          requestingToken = false;
          detail.fail();
          return;
        }

        execution.then(function (result) {
          requestingToken = false;

          var token = result && typeof result.response === "string"
            ? result.response
            : hcaptcha.getResponse(widgetId);

          if (!token) {
            detail.fail();
            return;
          }

          detail.complete(token);
        }).catch(function () {
          requestingToken = false;
          detail.fail();
        });
      } catch (error) {
        requestingToken = false;
        detail.fail();
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

  window.wpCaptchaShieldHCaptchaOnload = function () {
    if (
      typeof hcaptcha === "undefined" ||
      typeof hcaptcha.render !== "function" ||
      typeof hcaptcha.execute !== "function" ||
      typeof hcaptcha.getResponse !== "function"
    ) {
      return;
    }

    document.querySelectorAll(WIDGET_SELECTOR).forEach(initialize);
  };
})();
