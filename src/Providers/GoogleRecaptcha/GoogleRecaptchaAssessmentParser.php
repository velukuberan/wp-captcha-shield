<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\GoogleRecaptcha;

use JsonException;

final class GoogleRecaptchaAssessmentParser
{
    public function parse(
        string $responseBody,
    ): ?GoogleRecaptchaAssessment {
        try {
            $payload = json_decode(
                $responseBody,
                true,
                512,
                JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            return null;
        }

        if (!is_array($payload)) {
            return null;
        }

        $tokenProperties = $payload['tokenProperties'] ?? null;

        if (
            !is_array($tokenProperties)
            || !array_key_exists('valid', $tokenProperties)
            || !is_bool($tokenProperties['valid'])
        ) {
            return null;
        }

        if (!$tokenProperties['valid']) {
            return $this->parseInvalidAssessment($tokenProperties);
        }

        return $this->parseValidAssessment(
            $payload,
            $tokenProperties,
        );
    }

    /**
     * @param array<mixed> $tokenProperties
     */
    private function parseInvalidAssessment(
        array $tokenProperties,
    ): ?GoogleRecaptchaAssessment {
        $invalidReason = $tokenProperties['invalidReason'] ?? null;

        if (
            !is_string($invalidReason)
            || trim($invalidReason) === ''
        ) {
            return null;
        }

        return GoogleRecaptchaAssessment::invalid($invalidReason);
    }

    /**
     * @param array<mixed> $payload
     * @param array<mixed> $tokenProperties
     */
    private function parseValidAssessment(
        array $payload,
        array $tokenProperties,
    ): ?GoogleRecaptchaAssessment {
        $riskAnalysis = $payload['riskAnalysis'] ?? null;

        if (!is_array($riskAnalysis)) {
            return null;
        }

        $score = $riskAnalysis['score'] ?? null;

        if (!is_int($score) && !is_float($score)) {
            return null;
        }

        $score = (float) $score;

        if ($score < 0.0 || $score > 1.0) {
            return null;
        }

        $action = $tokenProperties['action'] ?? null;

        if ($action !== null && !is_string($action)) {
            return null;
        }

        return GoogleRecaptchaAssessment::valid(
            $score,
            $action,
        );
    }
}
