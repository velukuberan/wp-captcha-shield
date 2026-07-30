<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;

interface CaptchaVerifier
{
    public function provider(): CaptchaProvider;

    public function verify(
        CaptchaVerificationRequest $request,
    ): VerificationResult;
}
