<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms;

final class SupportedForms
{
    public const WORDPRESS_LOGIN = 'wordpress_login';

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
        ];
    }
}
