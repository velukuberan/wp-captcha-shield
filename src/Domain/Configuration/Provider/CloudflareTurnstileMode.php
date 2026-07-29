<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration\Provider;

enum CloudflareTurnstileMode: string
{
    case Managed = 'managed';
    case NonInteractive = 'non_interactive';
    case Invisible = 'invisible';
}
