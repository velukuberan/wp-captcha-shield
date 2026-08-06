<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\WooCommerce;

final class WooCommerceAvailability
{
    public function isAvailable(): bool
    {
        return class_exists('WooCommerce');
    }
}
