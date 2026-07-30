<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\HCaptcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class HCaptchaVerifier implements CaptchaVerifier
{
    private const SITEVERIFY_URL = 'https://api.hcaptcha.com/siteverify';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    public function __construct(
        private string $secretKey,
        private HttpClient $httpClient,
        private HCaptchaResponseParser $responseParser,
        private HCaptchaErrorMapper $errorMapper,
    ) {
    }

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::HCaptcha;
    }

    public function verify(
        string $token,
        ?string $remoteIp = null,
    ): VerificationResult {
        $token = trim($token);
        $secretKey = trim($this->secretKey);

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
