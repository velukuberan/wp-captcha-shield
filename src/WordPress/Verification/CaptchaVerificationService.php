<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Verification;

use LogicException;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class CaptchaVerificationService
{
    public function __construct(
        private CaptchaVerifierResolver $verifierResolver,
    ) {
    }

    public function verify(
        EffectiveCaptchaProvider $effectiveProvider,
        string $token,
        ?string $remoteIp = null,
    ): VerificationResult {
        if ($effectiveProvider->isDisabled()) {
            return VerificationResult::successful();
        }

        $provider = $effectiveProvider->provider();

        if ($provider === null) {
            return VerificationResult::misconfigured(
                VerificationFailureReason::InvalidConfiguration,
            );
        }

        try {
            $verifier = $this->verifierResolver->resolve(
                $provider,
            );
        } catch (LogicException) {
            return VerificationResult::misconfigured(
                VerificationFailureReason::MissingConfiguration,
            );
        }

        return $verifier->verify(
            $token,
            $remoteIp,
        );
    }
}
