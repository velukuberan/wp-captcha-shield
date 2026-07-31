<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

use WpCaptchaShield\Domain\Configuration\Provider\HCaptchaDisplayMode;

final class HCaptchaSettings
{
    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        private readonly HCaptchaDisplayMode $mode,
    ) {
    }

    public static function defaults(): self
    {
        return new self('', '', HCaptchaDisplayMode::Checkbox);
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }

    public function mode(): HCaptchaDisplayMode
    {
        return $this->mode;
    }
}
