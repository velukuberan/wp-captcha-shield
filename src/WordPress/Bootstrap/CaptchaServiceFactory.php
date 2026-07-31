<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Bootstrap;

use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileErrorMapper;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileResponseParser;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileVerifier;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaAssessmentParser;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaErrorMapper;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaVerifier;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaErrorMapper;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaResponseParser;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaVerifier;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CaptchaProviderConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\CloudflareTurnstileConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\GoogleRecaptchaConfiguration;
use WpCaptchaShield\WordPress\Bootstrap\Configuration\HCaptchaConfiguration;
use WpCaptchaShield\WordPress\Verification\CaptchaVerificationService;
use WpCaptchaShield\WordPress\Verification\CaptchaVerifierResolver;

final class CaptchaServiceFactory
{
    public function __construct(
        private HttpClient $httpClient,
    ) {
    }

    public function create(
        CaptchaProviderConfiguration $configuration,
    ): CaptchaVerificationService {
        return new CaptchaVerificationService(
            new CaptchaVerifierResolver(
                $this->createVerifiers($configuration),
            ),
        );
    }

    /**
     * @return list<CaptchaVerifier>
     */
    private function createVerifiers(
        CaptchaProviderConfiguration $configuration,
    ): array {
        $verifiers = [];

        if ($configuration->turnstile()->isConfigured()) {
            $verifiers[] = $this->createTurnstileVerifier(
                $configuration->turnstile(),
            );
        }

        if ($configuration->googleRecaptcha()->isConfigured()) {
            $verifiers[] = $this->createGoogleVerifier(
                $configuration->googleRecaptcha(),
            );
        }

        if ($configuration->hCaptcha()->isConfigured()) {
            $verifiers[] = $this->createHCaptchaVerifier(
                $configuration->hCaptcha(),
            );
        }

        return $verifiers;
    }

    private function createTurnstileVerifier(
        CloudflareTurnstileConfiguration $configuration,
    ): CaptchaVerifier {
        return new CloudflareTurnstileVerifier(
            $configuration->secretKey(),
            $this->httpClient,
            new CloudflareTurnstileResponseParser(),
            new CloudflareTurnstileErrorMapper(),
        );
    }

    private function createGoogleVerifier(
        GoogleRecaptchaConfiguration $configuration,
    ): CaptchaVerifier {
        return new GoogleRecaptchaVerifier(
            $configuration->projectId(),
            $configuration->apiKey(),
            $configuration->siteKey(),
            $configuration->minimumScore(),
            $this->httpClient,
            new GoogleRecaptchaAssessmentParser(),
            new GoogleRecaptchaErrorMapper(),
        );
    }

    private function createHCaptchaVerifier(
        HCaptchaConfiguration $configuration,
    ): CaptchaVerifier {
        return new HCaptchaVerifier(
            $configuration->secretKey(),
            $this->httpClient,
            new HCaptchaResponseParser(),
            new HCaptchaErrorMapper(),
        );
    }
}
