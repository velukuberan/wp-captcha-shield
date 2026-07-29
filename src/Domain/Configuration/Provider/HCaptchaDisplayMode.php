<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration\Provider;

enum HCaptchaDisplayMode: string
{
    case Checkbox = 'checkbox';
    case Invisible = 'invisible';
}
