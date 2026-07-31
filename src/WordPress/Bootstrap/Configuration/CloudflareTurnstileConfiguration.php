<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

final class CloudflareTurnstileConfiguration
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
