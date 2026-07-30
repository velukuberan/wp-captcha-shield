<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\GoogleRecaptcha;

use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class GoogleRecaptchaErrorMapper
{
    public function map(string $invalidReason): VerificationResult
    {
        return match (trim($invalidReason)) {
            'MISSING' => VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            ),
            'MALFORMED' => VerificationResult::failed(
                VerificationFailureReason::InvalidToken,
            ),
            'EXPIRED' => VerificationResult::failed(
                VerificationFailureReason::ExpiredToken,
            ),
            'DUPE' => VerificationResult::failed(
                VerificationFailureReason::DuplicateToken,
            ),
            'KEY_MISMATCH',
            'DOMAIN_MISMATCH' => VerificationResult::misconfigured(
                VerificationFailureReason::InvalidConfiguration,
            ),
            'BROWSER_ERROR',
            'UNEXPECTED_ACTION',
            'UNKNOWN_INVALID_REASON',
            'INVALID_REASON_UNSPECIFIED' => VerificationResult::failed(
                VerificationFailureReason::ProviderRejected,
            ),
            default => VerificationResult::failed(
                VerificationFailureReason::ProviderRejected,
            ),
        };
    }
}
