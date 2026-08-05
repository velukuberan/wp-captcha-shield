<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms;

final class SupportedForms
{
    public const WORDPRESS_LOGIN = 'wordpress_login';

    public const WORDPRESS_REGISTRATION = 'wordpress_registration';

    public const WORDPRESS_LOST_PASSWORD = 'wordpress_lost_password';

    /**
     * @return array<string, string>
     */
    public function labels(): array
    {
        return [
            self::WORDPRESS_LOGIN => __(
                'WordPress login',
                'wp-captcha-shield',
            ),
            self::WORDPRESS_REGISTRATION => __(
                'WordPress registration',
                'wp-captcha-shield',
            ),
            self::WORDPRESS_LOST_PASSWORD => __(
                'WordPress lost password',
                'wp-captcha-shield',
            ),
        ];
    }
}
