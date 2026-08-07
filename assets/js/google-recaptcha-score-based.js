(function () {
    'use strict';

    var TOKEN_SELECTOR = '.wp-captcha-shield-google-score-token';

    function initialize(tokenField) {
        var formId = tokenField.dataset.formId;
        var siteKey = tokenField.dataset.siteKey;
        var action = tokenField.dataset.action;
        var form = findForm(tokenField, formId);
        var requestingToken = false;

        if (!form || !siteKey || !action) {
            return;
        }

        form.addEventListener('submit', function (event) {
            var submitter = event.submitter || null;

            if (tokenField.value !== '') {
                return;
            }

            event.preventDefault();
            event.stopImmediatePropagation();

            if (requestingToken || !googleIsAvailable()) {
                return;
            }

            requestingToken = true;

            grecaptcha.enterprise.ready(function () {
                grecaptcha.enterprise.execute(siteKey, {
                    action: action
                }).then(function (token) {
                    tokenField.value = token;
                    requestingToken = false;
                    resumeSubmission(form, submitter);
                }).catch(function () {
                    requestingToken = false;
                    tokenField.value = '';
                });
            });
        }, true);
    }

    function findForm(element, formId) {
        if (formId) {
            var configuredForm = document.getElementById(formId);

            if (configuredForm) {
                return configuredForm;
            }
        }

        return element.closest('form');
    }

    function googleIsAvailable() {
        return typeof grecaptcha !== 'undefined'
            && typeof grecaptcha.enterprise !== 'undefined'
            && typeof grecaptcha.enterprise.ready === 'function'
            && typeof grecaptcha.enterprise.execute === 'function';
    }

    function resumeSubmission(form, submitter) {
        if (typeof form.requestSubmit === 'function') {
            if (submitter) {
                form.requestSubmit(submitter);
                return;
            }

            form.requestSubmit();
            return;
        }

        form.submit();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll(TOKEN_SELECTOR).forEach(initialize);
    });
}());
