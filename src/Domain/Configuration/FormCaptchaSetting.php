<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration;

use LogicException;

final class FormCaptchaSetting
{
    private function __construct(
        private readonly FormCaptchaMode $mode,
        private readonly ?CaptchaProvider $provider,
    ) {
    }

    public static function useDefault(): self
    {
        return new self(
            FormCaptchaMode::UseDefault,
            null,
        );
    }

    public static function disabled(): self
    {
        return new self(
            FormCaptchaMode::Disabled,
            null,
        );
    }

    public static function provider(CaptchaProvider $provider): self
    {
        return new self(
            FormCaptchaMode::Provider,
            $provider,
        );
    }

    public function usesDefault(): bool
    {
        return $this->mode === FormCaptchaMode::UseDefault;
    }

    public function isDisabled(): bool
    {
        return $this->mode === FormCaptchaMode::Disabled;
    }

    public function usesProvider(): bool
    {
        return $this->mode === FormCaptchaMode::Provider;
    }

    public function selectedProvider(): CaptchaProvider
    {
        if ($this->provider === null) {
            throw new LogicException(
                'The form setting does not contain a CAPTCHA provider.',
            );
        }

        return $this->provider;
    }
}
