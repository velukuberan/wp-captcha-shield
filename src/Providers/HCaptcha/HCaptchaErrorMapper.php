<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\HCaptcha;

use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class HCaptchaErrorMapper
{
    /**
     * Error-code order defines precedence when hCaptcha returns several codes.
     */
    private const ERROR_CODE_PRECEDENCE = [
        'missing-input-secret',
        'invalid-input-secret',
        'missing-input-response',
        'expired-input-response',
        'invalid-input-response',
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
            'missing-input-response' => VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            ),
            'expired-input-response' => VerificationResult::failed(
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
