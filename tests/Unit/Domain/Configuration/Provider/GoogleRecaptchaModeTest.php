<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Configuration\Provider;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;

final class GoogleRecaptchaModeTest extends TestCase
{
    public function testItDefinesTheSupportedModes(): void
    {
        self::assertSame(
            [
                GoogleRecaptchaMode::ScoreBased,
                GoogleRecaptchaMode::Checkbox,
                GoogleRecaptchaMode::Invisible,
            ],
            GoogleRecaptchaMode::cases(),
        );
    }

    public function testItDefinesStableStoredValues(): void
    {
        self::assertSame(
            'score_based',
            GoogleRecaptchaMode::ScoreBased->value,
        );
        self::assertSame(
            'checkbox',
            GoogleRecaptchaMode::Checkbox->value,
        );
        self::assertSame(
            'invisible',
            GoogleRecaptchaMode::Invisible->value,
        );
    }
}
