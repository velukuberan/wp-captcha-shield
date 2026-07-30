<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\GoogleRecaptcha;

use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class GoogleRecaptchaHttpStatusMapper
{
    public static function map(
        int $statusCode,
    ): ?VerificationResult {
        return match (true) {
            $statusCode >= 200 && $statusCode < 300 => null,

            in_array(
                $statusCode,
                [400, 401, 403, 404],
                true,
            ) => VerificationResult::misconfigured(
                VerificationFailureReason::InvalidConfiguration,
            ),

            default => VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            ),
        };
    }
}
