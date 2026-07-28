<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration;

final class GlobalCaptchaSetting
{
    private function __construct(
        private readonly ?CaptchaProvider $provider,
    ) {
    }

    public static function disabled(): self
    {
        return new self(null);
    }

    public static function provider(CaptchaProvider $provider): self
    {
        return new self($provider);
    }

    public function isDisabled(): bool
    {
        return $this->provider === null;
    }

    public function isEnabled(): bool
    {
        return $this->provider !== null;
    }

    public function selectedProvider(): ?CaptchaProvider
    {
        return $this->provider;
    }
}
