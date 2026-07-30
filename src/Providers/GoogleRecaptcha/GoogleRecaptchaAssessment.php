<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\GoogleRecaptcha;

use InvalidArgumentException;

final class GoogleRecaptchaAssessment
{
    private function __construct(
        private bool $valid,
        private ?string $invalidReason,
        private ?float $score,
        private ?string $action,
    ) {
        $this->assertValidState();
    }

    public static function valid(
        float $score,
        ?string $action = null,
    ): self {
        return new self(
            true,
            null,
            $score,
            self::normalizeOptionalValue($action),
        );
    }

    public static function invalid(string $invalidReason): self
    {
        return new self(
            false,
            self::normalizeRequiredValue($invalidReason),
            null,
            null,
        );
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function invalidReason(): ?string
    {
        return $this->invalidReason;
    }

    public function score(): ?float
    {
        return $this->score;
    }

    public function action(): ?string
    {
        return $this->action;
    }

    private function assertValidState(): void
    {
        if ($this->valid && $this->invalidReason !== null) {
            throw new InvalidArgumentException(
                'A valid reCAPTCHA assessment cannot have an invalid reason.',
            );
        }

        if ($this->valid && $this->score === null) {
            throw new InvalidArgumentException(
                'A valid reCAPTCHA assessment must have a score.',
            );
        }

        if (
            $this->score !== null
            && ($this->score < 0.0 || $this->score > 1.0)
        ) {
            throw new InvalidArgumentException(
                'A reCAPTCHA assessment score must be between 0 and 1.',
            );
        }

        if (!$this->valid && $this->invalidReason === null) {
            throw new InvalidArgumentException(
                'An invalid reCAPTCHA assessment must have a reason.',
            );
        }

        if (!$this->valid && $this->score !== null) {
            throw new InvalidArgumentException(
                'An invalid reCAPTCHA assessment cannot have a score.',
            );
        }

        if (!$this->valid && $this->action !== null) {
            throw new InvalidArgumentException(
                'An invalid reCAPTCHA assessment cannot have an action.',
            );
        }
    }

    private static function normalizeRequiredValue(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException(
                'A reCAPTCHA invalid reason cannot be empty.',
            );
        }

        return $value;
    }

    private static function normalizeOptionalValue(
        ?string $value,
    ): ?string {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
