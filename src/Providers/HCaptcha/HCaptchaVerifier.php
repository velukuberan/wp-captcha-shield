<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\HCaptcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class HCaptchaVerifier implements CaptchaVerifier
{
    private const SITEVERIFY_URL = 'https://api.hcaptcha.com/siteverify';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    public function __construct(
        private string $secretKey,
        private string $siteKey,
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
        CaptchaVerificationRequest $request,
    ): VerificationResult {
        $token = $request->token();
        $secretKey = trim($this->secretKey);
        $siteKey = trim($this->siteKey);

        $inputFailure = $this->validateInput(
            $token,
            $secretKey,
            $siteKey,
        );

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
                        $request->remoteIp(),
                        $siteKey,
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
        string $siteKey,
    ): ?VerificationResult {
        return match (true) {
            $token === '' => VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            ),
            $secretKey === '' || $siteKey === '' =>
            VerificationResult::misconfigured(
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
        string $siteKey,
    ): array {
        $body = [
            'secret' => $secretKey,
            'response' => $token,
            'sitekey' => $siteKey,
        ];

        if ($remoteIp !== null) {
            $body['remoteip'] = $remoteIp;
        }

        return $body;
    }
}
