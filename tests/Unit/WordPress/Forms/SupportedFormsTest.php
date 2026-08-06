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

        Functions\expect('__')
            ->once()
            ->with('WordPress lost password', 'wp-captcha-shield')
            ->andReturn('WordPress lost password');

        Functions\expect('__')
            ->once()
            ->with('WordPress comments', 'wp-captcha-shield')
            ->andReturn('WordPress comments');

        Functions\expect('__')
            ->once()
            ->with('WooCommerce login', 'wp-captcha-shield')
            ->andReturn('WooCommerce login');

        self::assertSame(
            [
                SupportedForms::WORDPRESS_LOGIN =>
                    'WordPress login',
                SupportedForms::WORDPRESS_REGISTRATION =>
                    'WordPress registration',
                SupportedForms::WORDPRESS_LOST_PASSWORD =>
                    'WordPress lost password',
                SupportedForms::WORDPRESS_COMMENTS =>
                    'WordPress comments',
                SupportedForms::WOOCOMMERCE_LOGIN =>
                    'WooCommerce login',
            ],
            (new SupportedForms())->labels(),
        );
    }
}
