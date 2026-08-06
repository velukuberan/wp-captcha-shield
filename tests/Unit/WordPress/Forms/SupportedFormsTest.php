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

    public function testItContainsTheSupportedForms(): void
    {
        $labels = [
            'WordPress login',
            'WordPress registration',
            'WordPress lost password',
            'WordPress comments',
            'WooCommerce login',
            'WooCommerce registration',
            'WooCommerce lost password',
        ];

        foreach ($labels as $label) {
            Functions\expect('__')
                ->once()
                ->with($label, 'wp-captcha-shield')
                ->andReturn($label);
        }

        self::assertSame(
            [
                SupportedForms::WORDPRESS_LOGIN => 'WordPress login',
                SupportedForms::WORDPRESS_REGISTRATION =>
                    'WordPress registration',
                SupportedForms::WORDPRESS_LOST_PASSWORD =>
                    'WordPress lost password',
                SupportedForms::WORDPRESS_COMMENTS => 'WordPress comments',
                SupportedForms::WOOCOMMERCE_LOGIN => 'WooCommerce login',
                SupportedForms::WOOCOMMERCE_REGISTRATION =>
                    'WooCommerce registration',
                SupportedForms::WOOCOMMERCE_LOST_PASSWORD =>
                    'WooCommerce lost password',
            ],
            (new SupportedForms())->labels(),
        );
    }
}
