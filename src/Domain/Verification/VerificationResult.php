<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

use InvalidArgumentException;

final class VerificationResult
{
    private function __construct(
        private VerificationStatus $status,
        private ?VerificationFailureReason $reason,
    ) {
        $this->assertValidState();
    }

    public static function successful(): self
    {
        return new self(
            VerificationStatus::Successful,
            null,
        );
    }

    public static function failed(
        VerificationFailureReason $reason,
    ): self {
        return new self(
            VerificationStatus::Failed,
            $reason,
        );
    }

    public static function unavailable(
        VerificationFailureReason $reason,
    ): self {
        return new self(
            VerificationStatus::Unavailable,
            $reason,
        );
    }

    public static function misconfigured(
        VerificationFailureReason $reason,
    ): self {
        return new self(
            VerificationStatus::Misconfigured,
            $reason,
        );
    }

    public function status(): VerificationStatus
    {
        return $this->status;
    }

    public function reason(): ?VerificationFailureReason
    {
        return $this->reason;
    }

    public function isSuccessful(): bool
    {
        return $this->status === VerificationStatus::Successful;
    }

    public function isFailed(): bool
    {
        return $this->status === VerificationStatus::Failed;
    }

    public function isUnavailable(): bool
    {
        return $this->status === VerificationStatus::Unavailable;
    }

    public function isMisconfigured(): bool
    {
        return $this->status === VerificationStatus::Misconfigured;
    }

    private function assertValidState(): void
    {
        if ($this->status === VerificationStatus::Successful) {
            if ($this->reason !== null) {
                throw new InvalidArgumentException(
                    'A successful verification result cannot contain a failure reason.',
                );
            }

            return;
        }

        if ($this->reason === null) {
            throw new InvalidArgumentException(
                'An unsuccessful verification result must contain a failure reason.',
            );
        }

        if (
            VerificationFailureReason::statusFor($this->reason)
            !== $this->status
        ) {
            throw new InvalidArgumentException(
                'The verification failure reason does not match the result status.',
            );
        }
    }
}
