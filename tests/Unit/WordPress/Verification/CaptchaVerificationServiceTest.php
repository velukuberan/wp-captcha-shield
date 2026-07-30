<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Verification;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Configuration\EffectiveCaptchaProvider;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\WordPress\Verification\CaptchaVerificationService;
use WpCaptchaShield\WordPress\Verification\CaptchaVerifierResolver;

final class CaptchaVerificationServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItSkipsVerificationWhenCaptchaIsDisabled(): void
    {
        $service = new CaptchaVerificationService(
            new CaptchaVerifierResolver([]),
        );

        $result = $service->verify(
            EffectiveCaptchaProvider::disabled(),
            new CaptchaVerificationRequest(''),
        );

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    public function testItDelegatesTheExactRequestToTheResolvedVerifier(): void
    {
        $request = new CaptchaVerificationRequest(
            'submitted-token',
            '203.0.113.10',
            'Mozilla/5.0',
            'wordpress_login',
        );

        $verifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $verifier
            ->shouldReceive('verify')
            ->once()
            ->with($request)
            ->andReturn(
                VerificationResult::successful(),
            );

        $service = $this->serviceWith($verifier);

        $result = $service->verify(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            $request,
        );

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    public function testItReturnsTheProviderFailureUnchanged(): void
    {
        $request = new CaptchaVerificationRequest(
            'submitted-token',
        );

        $verifier = $this->verifierFor(
            CaptchaProvider::CloudflareTurnstile,
        );

        $providerFailure = VerificationResult::failed(
            VerificationFailureReason::InvalidToken,
        );

        $verifier
            ->shouldReceive('verify')
            ->once()
            ->with($request)
            ->andReturn($providerFailure);

        $service = $this->serviceWith($verifier);

        $result = $service->verify(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::CloudflareTurnstile,
            ),
            $request,
        );

        self::assertSame($providerFailure, $result);
        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::InvalidToken,
            $result->reason(),
        );
    }

    public function testItMapsAMissingVerifierToMisconfigured(): void
    {
        $service = new CaptchaVerificationService(
            new CaptchaVerifierResolver([]),
        );

        $result = $service->verify(
            EffectiveCaptchaProvider::enabled(
                CaptchaProvider::HCaptcha,
            ),
            new CaptchaVerificationRequest(
                'submitted-token',
            ),
        );

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MissingConfiguration,
            $result->reason(),
        );
    }

    private function serviceWith(
        CaptchaVerifier $verifier,
    ): CaptchaVerificationService {
        return new CaptchaVerificationService(
            new CaptchaVerifierResolver([
                $verifier,
            ]),
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
