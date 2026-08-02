(function () {
  "use strict";

  var WIDGET_SELECTOR = ".wp-captcha-shield-google-invisible-widget";

  function initialize(container) {
    var formId = container.dataset.formId;
    var tokenId = container.dataset.tokenId;
    var siteKey = container.dataset.siteKey;
    var action = container.dataset.action;
    var form = document.getElementById(formId);
    var tokenField = document.getElementById(tokenId);
    var widgetId = null;
    var requestingToken = false;
    var submitter = null;

    if (!form || !tokenField || !siteKey || !action) {
      return;
    }

    grecaptcha.enterprise.ready(function () {
      widgetId = grecaptcha.enterprise.render(container, {
        sitekey: siteKey,
        size: "invisible",
        action: action,
        callback: function (token) {
          tokenField.value = token;
          requestingToken = false;
          resumeSubmission(form, submitter);
        },
        "expired-callback": function () {
          tokenField.value = "";
          requestingToken = false;
        },
        "error-callback": function () {
          tokenField.value = "";
          requestingToken = false;
        },
      });
    });

    form.addEventListener("submit", function (event) {
      if (tokenField.value !== "") {
        return;
      }

      event.preventDefault();

      if (requestingToken || widgetId === null) {
        return;
      }

      requestingToken = true;
      submitter = event.submitter || null;

      grecaptcha.enterprise.execute(widgetId).catch(function () {
        requestingToken = false;
        tokenField.value = "";
      });
    });
  }

  function googleIsAvailable() {
    return (
      typeof grecaptcha !== "undefined" &&
      typeof grecaptcha.enterprise !== "undefined" &&
      typeof grecaptcha.enterprise.ready === "function" &&
      typeof grecaptcha.enterprise.render === "function" &&
      typeof grecaptcha.enterprise.execute === "function"
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

  document.addEventListener("DOMContentLoaded", function () {
    if (!googleIsAvailable()) {
      return;
    }

    document.querySelectorAll(WIDGET_SELECTOR).forEach(initialize);
  });
})();
