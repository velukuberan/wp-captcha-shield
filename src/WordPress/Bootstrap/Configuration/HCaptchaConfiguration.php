<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

final class HCaptchaConfiguration
{
    public function __construct(
        private string $secretKey,
        private string $siteKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->secretKey) !== ''
            && trim($this->siteKey) !== '';
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }
}
