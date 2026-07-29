<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

enum VerificationStatus: string
{
    case Successful = 'successful';
    case Failed = 'failed';
    case Unavailable = 'unavailable';
    case Misconfigured = 'misconfigured';
}
