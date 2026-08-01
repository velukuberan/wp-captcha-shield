<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms\Captcha;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;

final class CaptchaWidgetContextTest extends TestCase
{
    public function testItExposesTheFormActionAndId(): void
    {
        $context = new CaptchaWidgetContext(
            'wordpress_login',
            'loginform',
        );

        self::assertSame('wordpress_login', $context->action());
        self::assertSame('loginform', $context->formId());
    }
}
