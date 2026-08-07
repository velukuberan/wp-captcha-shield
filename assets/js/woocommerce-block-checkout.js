(function () {
    'use strict';

    var CHECKOUT_STORE = 'wc/store/checkout';
    var EXTENSION_NAMESPACE = 'wp-captcha-shield';
    var ROOT_SELECTOR = '[data-wp-captcha-shield-block-checkout]';
    var PLACE_ORDER_SELECTOR = '.wc-block-components-checkout-place-order-button';
    var REQUEST_TOKEN_EVENT = 'wp-captcha-shield:request-token';
    var bypassButton = null;
    var requestInProgress = false;

    function findTokenField(root, fieldName) {
        var fields = root.querySelectorAll('[name]');
        var field = Array.prototype.find.call(fields, function (candidate) {
            return candidate.getAttribute('name') === fieldName;
        });

        if (field) {
            return field;
        }

        fields = document.querySelectorAll('[name]');

        return Array.prototype.find.call(fields, function (candidate) {
            return candidate.getAttribute('name') === fieldName;
        }) || null;
    }

    function tokenValue(root) {
        var fieldName = root.getAttribute('data-token-field');

        if (!fieldName) {
            return '';
        }

        var field = findTokenField(root, fieldName);

        if (!field || typeof field.value !== 'string') {
            return '';
        }

        return field.value;
    }

    function checkoutDispatcher() {
        if (!window.wp || !window.wp.data) {
            return null;
        }

        return window.wp.data.dispatch(CHECKOUT_STORE);
    }

    function setExtensionToken(token) {
        var dispatcher = checkoutDispatcher();

        if (!dispatcher || typeof dispatcher.setExtensionData !== 'function') {
            return false;
        }

        dispatcher.setExtensionData(EXTENSION_NAMESPACE, {
            token: token
        });

        return true;
    }

    function extensionToken() {
        if (!window.wp || !window.wp.data) {
            return '';
        }

        var selector = window.wp.data.select(CHECKOUT_STORE);

        if (!selector || typeof selector.getExtensionData !== 'function') {
            return '';
        }

        var extensions = selector.getExtensionData();
        var extension = extensions && extensions[EXTENSION_NAMESPACE];

        return extension && typeof extension.token === 'string'
            ? extension.token
            : '';
    }

    function syncExistingToken(root) {
        var token = tokenValue(root);

        if (!token) {
            return false;
        }

        return setExtensionToken(token);
    }

    function failure(root) {
        return {
            errorMessage: root.getAttribute('data-error-message')
                || 'CAPTCHA verification failed. Please try again.'
        };
    }

    function executableTokenRequest(root) {
        var settled = false;
        var timeout = null;
        var resolvePromise = null;
        var promise = new Promise(function (resolve) {
            resolvePromise = resolve;
        });
        var detail = {
            root: root,
            handled: false,
            complete: function (token) {
                if (settled) {
                    return;
                }

                settled = true;
                window.clearTimeout(timeout);
                resolvePromise(typeof token === 'string' ? token : '');
            },
            fail: function () {
                if (settled) {
                    return;
                }

                settled = true;
                window.clearTimeout(timeout);
                resolvePromise('');
            }
        };

        document.dispatchEvent(new CustomEvent(REQUEST_TOKEN_EVENT, {
            detail: detail
        }));

        if (!detail.handled) {
            settled = true;
            return null;
        }

        timeout = window.setTimeout(function () {
            detail.fail();
        }, 120000);

        return promise;
    }

    function resumePlaceOrder(button, token) {
        setExtensionToken(token);
        bypassButton = button;
        button.click();
    }

    function handlePlaceOrder(event) {
        var target = event.target;

        if (!(target instanceof Element)) {
            return;
        }

        var button = target.closest(PLACE_ORDER_SELECTOR);
        var root = document.querySelector(ROOT_SELECTOR);

        if (!button || !root) {
            return;
        }

        if (bypassButton === button) {
            bypassButton = null;
            return;
        }

        if (requestInProgress) {
            event.preventDefault();
            event.stopImmediatePropagation();
            return;
        }

        var request = executableTokenRequest(root);

        if (request === null) {
            syncExistingToken(root);
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        requestInProgress = true;

        request.then(function (token) {
            requestInProgress = false;
            resumePlaceOrder(button, token);
        });
    }

    function validateCheckout() {
        var root = document.querySelector(ROOT_SELECTOR);

        if (!root) {
            return true;
        }

        if (!extensionToken()) {
            return failure(root);
        }

        return true;
    }

    function observeToken(root) {
        var observer = new MutationObserver(function () {
            syncExistingToken(root);
        });

        observer.observe(root, {
            childList: true,
            subtree: true,
            attributes: true
        });

        syncExistingToken(root);
    }

    function registerCheckoutValidation(attempt) {
        if (
            window.wc
            && window.wc.blocksCheckoutEvents
            && typeof window.wc.blocksCheckoutEvents.onCheckoutValidation === 'function'
        ) {
            window.wc.blocksCheckoutEvents.onCheckoutValidation(
                validateCheckout,
                10
            );
            return;
        }

        if (attempt < 50) {
            window.setTimeout(function () {
                registerCheckoutValidation(attempt + 1);
            }, 100);
        }
    }

    function initialize() {
        var root = document.querySelector(ROOT_SELECTOR);

        if (!root) {
            return;
        }

        observeToken(root);
        document.addEventListener('click', handlePlaceOrder, true);
        registerCheckoutValidation(0);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }
}());
