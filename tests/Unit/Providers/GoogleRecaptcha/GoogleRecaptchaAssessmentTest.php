<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaAssessment;

final class GoogleRecaptchaAssessmentTest extends TestCase
{
    public function testItCreatesAValidAssessment(): void
    {
        $assessment = GoogleRecaptchaAssessment::valid(
            0.9,
            ' wordpress_login ',
        );

        self::assertTrue($assessment->isValid());
        self::assertNull($assessment->invalidReason());
        self::assertSame(0.9, $assessment->score());
        self::assertSame(
            'wordpress_login',
            $assessment->action(),
        );
    }

    public function testItCreatesAnInvalidAssessment(): void
    {
        $assessment = GoogleRecaptchaAssessment::invalid(
            ' EXPIRED ',
        );

        self::assertFalse($assessment->isValid());
        self::assertSame(
            'EXPIRED',
            $assessment->invalidReason(),
        );
        self::assertNull($assessment->score());
        self::assertNull($assessment->action());
    }

    #[DataProvider('invalidScoreCases')]
    public function testItRejectsAnOutOfRangeScore(float $score): void
    {
        $this->expectException(InvalidArgumentException::class);

        GoogleRecaptchaAssessment::valid($score);
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidScoreCases(): iterable
    {
        yield 'below zero' => [-0.1];
        yield 'above one' => [1.1];
    }

    public function testItRejectsAnEmptyInvalidReason(): void
    {
        $this->expectException(InvalidArgumentException::class);

        GoogleRecaptchaAssessment::invalid('   ');
    }
}
