<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Domain\Environment;

use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Environment\EnvironmentCompatibility;

final class EnvironmentCompatibilityTest extends TestCase
{
    public function testItTreatsAnExactMatchAsCompatible(): void
    {
        $compatibility = new EnvironmentCompatibility();

        self::assertTrue($compatibility->isAtLeast('6.7.0', '6.7.0'));
    }

    public function testItTreatsANewerVersionAsCompatible(): void
    {
        $compatibility = new EnvironmentCompatibility();

        self::assertTrue($compatibility->isAtLeast('8.2.10', '8.1.0'));
    }

    public function testItTreatsAnOlderVersionAsIncompatible(): void
    {
        $compatibility = new EnvironmentCompatibility();

        self::assertFalse($compatibility->isAtLeast('6.6', '6.7.0'));
    }

    public function testItTreatsAShortRoundReleaseVersionAsCompatible(): void
    {
        // WordPress reports "round" releases without a trailing patch
        // component (e.g. "6.7" for the initial 6.7 release, not
        // "6.7.0"). A naive version_compare() against the minimum
        // "6.7.0" would incorrectly flag this as unsupported.
        $compatibility = new EnvironmentCompatibility();

        self::assertTrue($compatibility->isAtLeast('6.7', '6.7.0'));
    }

    public function testItTreatsAVersionWithASingleComponentAsCompatible(): void
    {
        $compatibility = new EnvironmentCompatibility();

        self::assertTrue($compatibility->isAtLeast('7', '6.7.0'));
    }
}
