<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;

interface CaptchaVerifierProvider
{
    public function resolve(
        CaptchaProvider $provider,
    ): CaptchaVerifier;
}
