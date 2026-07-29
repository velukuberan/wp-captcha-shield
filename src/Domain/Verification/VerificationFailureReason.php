<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

enum VerificationFailureReason: string
{
    case MissingToken = 'missing_token';
    case InvalidToken = 'invalid_token';
    case ExpiredToken = 'expired_token';
    case DuplicateToken = 'duplicate_token';
    case LowScore = 'low_score';
    case ProviderRejected = 'provider_rejected';
    case NetworkFailure = 'network_failure';
    case MalformedResponse = 'malformed_response';
    case MissingConfiguration = 'missing_configuration';
    case InvalidConfiguration = 'invalid_configuration';

    public static function statusFor(
        VerificationFailureReason $reason,
    ): VerificationStatus {
        return match ($reason) {
            self::MissingToken,
            self::InvalidToken,
            self::ExpiredToken,
            self::DuplicateToken,
            self::LowScore,
            self::ProviderRejected => VerificationStatus::Failed,

            self::NetworkFailure,
            self::MalformedResponse => VerificationStatus::Unavailable,

            self::MissingConfiguration,
            self::InvalidConfiguration => VerificationStatus::Misconfigured,
        };
    }
}
