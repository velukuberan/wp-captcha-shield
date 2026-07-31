<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap\Configuration;

final class GoogleRecaptchaConfiguration
{
    public function __construct(
        private string $projectId,
        private string $apiKey,
        private string $siteKey,
        private float $minimumScore,
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->projectId) !== ''
            && trim($this->apiKey) !== ''
            && trim($this->siteKey) !== '';
    }

    public function projectId(): string
    {
        return $this->projectId;
    }

    public function apiKey(): string
    {
        return $this->apiKey;
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function minimumScore(): float
    {
        return $this->minimumScore;
    }
}
