<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;

final class GoogleRecaptchaSettings
{
    private const DEFAULT_MINIMUM_SCORE = 0.5;

    public function __construct(
        private readonly string $projectId,
        private readonly string $apiKey,
        private readonly string $siteKey,
        private readonly float $minimumScore,
        private readonly GoogleRecaptchaMode $mode,
    ) {
    }

    public static function defaults(): self
    {
        return new self(
            '',
            '',
            '',
            self::DEFAULT_MINIMUM_SCORE,
            GoogleRecaptchaMode::ScoreBased,
        );
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

    public function mode(): GoogleRecaptchaMode
    {
        return $this->mode;
    }
}
