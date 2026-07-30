<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Verification;

use LogicException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\WordPress\Verification\CaptchaVerifierResolver;

final class CaptchaVerifierResolverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItResolvesARegisteredVerifier(): void
    {
        $turnstileVerifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $resolver = new CaptchaVerifierResolver([
            $turnstileVerifier,
        ]);

        self::assertSame(
            $turnstileVerifier,
            $resolver->resolve(
                CaptchaProvider::CloudflareTurnstile,
            ),
        );
    }

    public function testItResolvesEachVerifierByProvider(): void
    {
        $turnstileVerifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $hCaptchaVerifier = $this->verifierFor(
            CaptchaProvider::HCaptcha,
        );

        $resolver = new CaptchaVerifierResolver([
            $turnstileVerifier,
            $hCaptchaVerifier,
        ]);

        self::assertSame(
            $turnstileVerifier,
            $resolver->resolve(
                CaptchaProvider::CloudflareTurnstile,
            ),
        );

        self::assertSame(
            $hCaptchaVerifier,
            $resolver->resolve(
                CaptchaProvider::HCaptcha,
            ),
        );
    }

    public function testItRejectsDuplicateProviderRegistrations(): void
    {
        $firstVerifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $secondVerifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'A CAPTCHA verifier is already registered for ' .
            '"cloudflare_turnstile".',
        );

        new CaptchaVerifierResolver([
            $firstVerifier,
            $secondVerifier,
        ]);
    }

    public function testItRejectsAnUnregisteredProvider(): void
    {
        $turnstileVerifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $resolver = new CaptchaVerifierResolver([
            $turnstileVerifier,
        ]);

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage(
            'No CAPTCHA verifier is registered for "hcaptcha".',
        );

        $resolver->resolve(
            CaptchaProvider::HCaptcha,
        );
    }

    private function verifierFor(
        CaptchaProvider $provider,
    ): CaptchaVerifier&MockInterface {
        /** @var CaptchaVerifier&MockInterface $verifier */
        $verifier = Mockery::mock(CaptchaVerifier::class);

        $verifier
            ->shouldReceive('provider')
            ->once()
            ->andReturn($provider);

        return $verifier;
    }
}
