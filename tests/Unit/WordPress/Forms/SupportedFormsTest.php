<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Forms;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\WordPress\Forms\SupportedForms;

final class SupportedFormsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }

    public function testItContainsTheWordPressLoginForm(): void
    {
        Functions\expect('__')
            ->once()
            ->with('WordPress login', 'wp-captcha-shield')
            ->andReturn('WordPress login');

        self::assertSame(
            [
                SupportedForms::WORDPRESS_LOGIN => 'WordPress login',
            ],
            (new SupportedForms())->labels(),
        );
    }
}
