<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\CloudflareTurnstile;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class CloudflareTurnstileVerifier implements CaptchaVerifier
{
    private const SITEVERIFY_URL =
        'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    private const MAXIMUM_TOKEN_LENGTH = 2048;

    public function __construct(
        private HttpClient $httpClient,
        private CloudflareTurnstileResponseParser $responseParser,
        private CloudflareTurnstileErrorMapper $errorMapper,
    ) {
    }

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::CloudflareTurnstile;
    }

    public function verify(
        string $token,
        string $secretKey,
        ?string $remoteIp = null,
    ): VerificationResult {
        $token = trim($token);
        $secretKey = trim($secretKey);

        $inputFailure = $this->validateInput($token, $secretKey);

        if ($inputFailure !== null) {
            return $inputFailure;
        }

        try {
            $httpResponse = $this->httpClient->post(
                self::SITEVERIFY_URL,
                [
                    'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                    'body' => $this->requestBody(
                        $token,
                        $secretKey,
                        $remoteIp,
                    ),
                ],
            );
        } catch (HttpClientException) {
            return VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            );
        }

        if (
            $httpResponse->statusCode() < 200
            || $httpResponse->statusCode() >= 300
        ) {
            return VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            );
        }

        $providerResponse = $this->responseParser->parse(
            $httpResponse->body(),
        );

        return match (true) {
            $providerResponse === null =>
            VerificationResult::unavailable(
                VerificationFailureReason::MalformedResponse,
            ),
            $providerResponse->isSuccessful() =>
            VerificationResult::successful(),
            default =>
            $this->errorMapper->map(
                $providerResponse->errorCodes(),
            ),
        };
    }

    private function validateInput(
        string $token,
        string $secretKey,
    ): ?VerificationResult {
        return match (true) {
            $token === '' => VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            ),
            strlen($token) > self::MAXIMUM_TOKEN_LENGTH =>
            VerificationResult::failed(
                VerificationFailureReason::InvalidToken,
            ),
            $secretKey === '' => VerificationResult::misconfigured(
                VerificationFailureReason::MissingConfiguration,
            ),
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function requestBody(
        string $token,
        string $secretKey,
        ?string $remoteIp,
    ): array {
        $body = [
            'secret' => $secretKey,
            'response' => $token,
        ];

        if ($remoteIp !== null && trim($remoteIp) !== '') {
            $body['remoteip'] = trim($remoteIp);
        }

        return $body;
    }
}
