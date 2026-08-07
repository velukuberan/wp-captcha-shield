(function () {
  "use strict";

  var WIDGET_SELECTOR = ".wp-captcha-shield-hcaptcha-invisible-widget";

  function initialize(container) {
    var formId = container.dataset.formId;
    var siteKey = container.dataset.siteKey;
    var form = findForm(container, formId);
    var widgetId = null;
    var requestingToken = false;
    var submitter = null;

    if (!form || !siteKey) {
      return;
    }

    widgetId = hcaptcha.render(container, {
      sitekey: siteKey,
      size: "invisible",
      callback: function () {
        requestingToken = false;
        resumeSubmission(form, submitter);
      },
      "expired-callback": function () {
        requestingToken = false;
      },
      "error-callback": function () {
        requestingToken = false;
      },
      "close-callback": function () {
        requestingToken = false;
      },
    });

    form.addEventListener("submit", function (event) {
      if (widgetId !== null && hcaptcha.getResponse(widgetId) !== "") {
        return;
      }

      event.preventDefault();
      event.stopImmediatePropagation();

      if (requestingToken || widgetId === null) {
        return;
      }

      requestingToken = true;
      submitter = event.submitter || null;

      try {
        hcaptcha.execute(widgetId);
      } catch (error) {
        requestingToken = false;
      }
    }, true);
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
