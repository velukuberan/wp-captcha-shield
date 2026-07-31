<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

final class HCaptchaConfiguration
{
    public function __construct(
        private string $secretKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->secretKey) !== '';
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }
}
