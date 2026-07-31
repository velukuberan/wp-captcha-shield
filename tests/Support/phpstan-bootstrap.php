<?php

declare(strict_types=1);

if (!defined('WP_CAPTCHA_SHIELD_VERSION')) {
    define('WP_CAPTCHA_SHIELD_VERSION', '0.1.0');
}

if (!defined('WP_CAPTCHA_SHIELD_FILE')) {
    define(
        'WP_CAPTCHA_SHIELD_FILE',
        dirname(__DIR__, 2) . '/wp-captcha-shield.php',
    );
}

if (!defined('WP_CAPTCHA_SHIELD_PATH')) {
    define(
        'WP_CAPTCHA_SHIELD_PATH',
        dirname(__DIR__, 2) . '/',
    );
}

if (!defined('WP_CAPTCHA_SHIELD_URL')) {
    define(
        'WP_CAPTCHA_SHIELD_URL',
        'https://example.test/wp-content/plugins/wp-captcha-shield/',
    );
}
