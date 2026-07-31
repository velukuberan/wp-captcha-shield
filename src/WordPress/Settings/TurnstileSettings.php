<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Settings;

use WpCaptchaShield\Domain\Configuration\Provider\CloudflareTurnstileMode;

final class TurnstileSettings
{
    public function __construct(
        private readonly string $siteKey,
        private readonly string $secretKey,
        private readonly CloudflareTurnstileMode $mode,
    ) {
    }

    public static function defaults(): self
    {
        return new self('', '', CloudflareTurnstileMode::Managed);
    }

    public function siteKey(): string
    {
        return $this->siteKey;
    }

    public function secretKey(): string
    {
        return $this->secretKey;
    }

    public function mode(): CloudflareTurnstileMode
    {
        return $this->mode;
    }
}
