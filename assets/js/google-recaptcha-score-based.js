(function () {
    'use strict';

    var TOKEN_SELECTOR = '.wp-captcha-shield-google-score-token';
    var REQUEST_TOKEN_EVENT = 'wp-captcha-shield:request-token';

    function initialize(tokenField) {
        var formId = tokenField.dataset.formId;
        var siteKey = tokenField.dataset.siteKey;
        var action = tokenField.dataset.action;
        var form = findForm(tokenField, formId);
        var blockRoot = tokenField.closest('[data-wp-captcha-shield-block-checkout]');
        var requestingToken = false;

        if ((!form && !blockRoot) || !siteKey || !action) {
            return;
        }

        function requestToken(onSuccess, onFailure) {
            if (requestingToken || !googleIsAvailable()) {
                onFailure();
                return;
            }

            requestingToken = true;

            grecaptcha.enterprise.ready(function () {
                grecaptcha.enterprise.execute(siteKey, {
                    action: action
                }).then(function (token) {
                    tokenField.value = token;
                    requestingToken = false;
                    onSuccess(token);
                }).catch(function () {
                    requestingToken = false;
                    tokenField.value = '';
                    onFailure();
                });
            });
        }

        if (form) {
            form.addEventListener('submit', function (event) {
                var submitter = event.submitter || null;

                if (tokenField.value !== '') {
                    return;
                }

                event.preventDefault();
                event.stopImmediatePropagation();

                requestToken(function () {
                    resumeSubmission(form, submitter);
                }, function () {});
            }, true);
        }

        document.addEventListener(REQUEST_TOKEN_EVENT, function (event) {
            var detail = event.detail;

            if (!detail || !detail.root || !detail.root.contains(tokenField)) {
                return;
            }

            detail.handled = true;
            tokenField.value = '';
            requestToken(detail.complete, detail.fail);
        });
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
