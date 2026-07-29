<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration\Provider;

enum GoogleRecaptchaMode: string
{
    case ScoreBased = 'score_based';
    case Checkbox = 'checkbox';
    case Invisible = 'invisible';
}
