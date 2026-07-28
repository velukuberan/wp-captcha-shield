<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration;

enum FormCaptchaMode
{
    case UseDefault;
    case Disabled;
    case Provider;
}
