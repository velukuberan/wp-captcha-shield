<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\GoogleRecaptcha;

use JsonException;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationResult;

final class GoogleRecaptchaVerifier implements CaptchaVerifier
{
    private const API_BASE_URL =
        'https://recaptchaenterprise.googleapis.com/v1/projects/';

    private const REQUEST_TIMEOUT_SECONDS = 10;

    public function __construct(
        private string $projectId,
        private string $apiKey,
        private string $siteKey,
        private float $minimumScore,
        private HttpClient $httpClient,
        private GoogleRecaptchaAssessmentParser $assessmentParser,
        private GoogleRecaptchaErrorMapper $errorMapper,
    ) {
    }

    public function provider(): CaptchaProvider
    {
        return CaptchaProvider::GoogleRecaptcha;
    }

    public function verify(
        CaptchaVerificationRequest $request,
    ): VerificationResult {
        $inputFailure = $this->validateRequest($request);

        if ($inputFailure !== null) {
            return $inputFailure;
        }

        try {
            $response = $this->sendAssessment($request);
        } catch (HttpClientException) {
            return VerificationResult::unavailable(
                VerificationFailureReason::NetworkFailure,
            );
        } catch (JsonException) {
            return VerificationResult::failed(
                VerificationFailureReason::InvalidToken,
            );
        }

        $statusFailure = GoogleRecaptchaHttpStatusMapper::map(
            $response->statusCode(),
        );

        if ($statusFailure !== null) {
            return $statusFailure;
        }

        return $this->evaluateAssessment(
            $response->body(),
            $request,
        );
    }

    private function validateRequest(
        CaptchaVerificationRequest $request,
    ): ?VerificationResult {
        $configurationFailure = $this->validateConfiguration();

        if ($configurationFailure !== null) {
            return $configurationFailure;
        }

        if ($request->token() === '') {
            return VerificationResult::failed(
                VerificationFailureReason::MissingToken,
            );
        }

        return null;
    }

    private function validateConfiguration(): ?VerificationResult
    {
        return match (true) {
            trim($this->projectId) === '',
            trim($this->apiKey) === '',
            trim($this->siteKey) === '' =>
            VerificationResult::misconfigured(
                VerificationFailureReason::MissingConfiguration,
            ),
            $this->minimumScore < 0.0,
            $this->minimumScore > 1.0 =>
            VerificationResult::misconfigured(
                VerificationFailureReason::InvalidConfiguration,
            ),
            default => null,
        };
    }

    /**
     * @throws HttpClientException
     * @throws JsonException
     */
    private function sendAssessment(
        CaptchaVerificationRequest $request,
    ): HttpResponse {
        return $this->httpClient->post(
            $this->assessmentUrl(),
            [
                'timeout' => self::REQUEST_TIMEOUT_SECONDS,
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
                'body' => $this->requestBody($request),
            ],
        );
    }

    private function evaluateAssessment(
        string $responseBody,
        CaptchaVerificationRequest $request,
    ): VerificationResult {
        $assessment = $this->assessmentParser->parse(
            $responseBody,
        );

        if ($assessment === null) {
            return VerificationResult::unavailable(
                VerificationFailureReason::MalformedResponse,
            );
        }

        if (!$assessment->isValid()) {
            return $this->mapInvalidAssessment($assessment);
        }

        if (!$this->hasExpectedAction($assessment, $request)) {
            return VerificationResult::failed(
                VerificationFailureReason::ProviderRejected,
            );
        }

        $score = $assessment->score();

        if ($score === null) {
            return VerificationResult::unavailable(
                VerificationFailureReason::MalformedResponse,
            );
        }

        if ($score < $this->minimumScore) {
            return VerificationResult::failed(
                VerificationFailureReason::LowScore,
            );
        }

        return VerificationResult::successful();
    }

    private function mapInvalidAssessment(
        GoogleRecaptchaAssessment $assessment,
    ): VerificationResult {
        $invalidReason = $assessment->invalidReason();

        if ($invalidReason === null) {
            return VerificationResult::unavailable(
                VerificationFailureReason::MalformedResponse,
            );
        }

        return $this->errorMapper->map($invalidReason);
    }

    private function hasExpectedAction(
        GoogleRecaptchaAssessment $assessment,
        CaptchaVerificationRequest $request,
    ): bool {
        return $request->expectedAction() === null
            || $assessment->action() === $request->expectedAction();
    }

    private function assessmentUrl(): string
    {
        return self::API_BASE_URL
            . rawurlencode(trim($this->projectId))
            . '/assessments?key='
            . rawurlencode(trim($this->apiKey));
    }

    /**
     * @throws JsonException
     */
    private function requestBody(
        CaptchaVerificationRequest $request,
    ): string {
        $event = [
            'token' => $request->token(),
            'siteKey' => trim($this->siteKey),
        ];

        if ($request->remoteIp() !== null) {
            $event['userIpAddress'] = $request->remoteIp();
        }

        if ($request->userAgent() !== null) {
            $event['userAgent'] = $request->userAgent();
        }

        if ($request->expectedAction() !== null) {
            $event['expectedAction'] = $request->expectedAction();
        }

        return json_encode(
            ['event' => $event],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
        );
    }
}
