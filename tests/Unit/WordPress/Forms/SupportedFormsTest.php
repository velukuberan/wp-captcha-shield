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

    public function testItContainsTheSupportedWordPressForms(): void
    {
        Functions\expect('__')
            ->once()
            ->with('WordPress login', 'wp-captcha-shield')
            ->andReturn('WordPress login');

        Functions\expect('__')
            ->once()
            ->with('WordPress registration', 'wp-captcha-shield')
            ->andReturn('WordPress registration');

        self::assertSame(
            [
                SupportedForms::WORDPRESS_LOGIN => 'WordPress login',
                SupportedForms::WORDPRESS_REGISTRATION =>
                    'WordPress registration',
            ],
            (new SupportedForms())->labels(),
        );
    }
}
