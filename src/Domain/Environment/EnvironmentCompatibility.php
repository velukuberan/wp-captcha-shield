<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Environment;

/**
 * Compares a "current" version string (as reported by PHP, WordPress, or
 * WooCommerce) against a minimum supported version.
 *
 * WordPress (and occasionally WooCommerce) reports "round" releases
 * without a trailing patch component — e.g. "6.7" instead of "6.7.0"
 * for the initial 6.7 release. PHP's version_compare() treats a short
 * version string as *older* than the same version written with an
 * explicit ".0" (version_compare('6.7', '6.7.0') returns -1), which
 * would otherwise flag a fully up-to-date site as unsupported. Padding
 * the current version to three numeric components before comparing
 * avoids that false negative.
 */
final class EnvironmentCompatibility
{
    public function isAtLeast(
        string $currentVersion,
        string $minimumVersion,
    ): bool {
        return version_compare(
            $this->normalizeVersion($currentVersion),
            $minimumVersion,
            '>=',
        );
    }

    private function normalizeVersion(string $version): string
    {
        $parts = explode('.', $version, 3);

        return implode('.', array_pad($parts, 3, '0'));
    }
}
