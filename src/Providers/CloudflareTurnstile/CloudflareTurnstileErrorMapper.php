<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\CloudflareTurnstile;

use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class CloudflareTurnstileErrorMapper
{
    /**
     * Error-code order defines precedence when Cloudflare returns several codes.
     */
    private const ERROR_CODE_PRECEDENCE = [
        'missing-input-secret',
        'invalid-input-secret',
        'internal-error',
        'missing-input-response',
        'timeout-or-duplicate',
        'invalid-input-response',
        'bad-request',
    ];

    /**
     * @param list<string> $errorCodes
     */
    public function map(array $errorCodes): VerificationResult
    {
        foreach (self::ERROR_CODE_PRECEDENCE as $knownErrorCode) {
            if (in_array($knownErrorCode, $errorCodes, true)) {
                return $this->mapKnownErrorCode($knownErrorCode);
            }
        }

        return VerificationResult::failed(
            VerificationFailureReason::ProviderRejected,
        );
    }

    private function mapKnownErrorCode(
        string $errorCode,
    ): VerificationResult {
        return match ($errorCode) {
            'missing-input-secret' => VerificationResult::misconfigured(
                VerificationFailureReason::MissingConfiguration,
            ),
            'invalid-input-secret' => VerificationResult::misconfigured(
                VerificationFailureReason::InvalidConfiguration,
            ),
            'internal-error' => VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            ),
            'missing-input-response' => VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            ),
            'timeout-or-duplicate' => VerificationResult::failed(
                VerificationFailureReason::DuplicateToken,
            ),
            'invalid-input-response' => VerificationResult::failed(
                VerificationFailureReason::InvalidToken,
            ),
            default => VerificationResult::failed(
                VerificationFailureReason::ProviderRejected,
            ),
        };
    }
}
