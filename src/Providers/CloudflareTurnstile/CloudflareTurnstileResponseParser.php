<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\CloudflareTurnstile;

use JsonException;

final class CloudflareTurnstileResponseParser
{
    public function parse(
        string $responseBody,
    ): ?CloudflareTurnstileResponse {
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

        if (
            !is_array($payload)
            || !array_key_exists('success', $payload)
            || !is_bool($payload['success'])
        ) {
            return null;
        }

        $errorCodes = $this->extractErrorCodes($payload);

        if ($payload['success']) {
            if ($errorCodes === null || $errorCodes !== []) {
                return null;
            }

            return CloudflareTurnstileResponse::successful();
        }

        if ($errorCodes === null || $errorCodes === []) {
            return null;
        }

        return CloudflareTurnstileResponse::rejected($errorCodes);
    }

    /**
     * @param array<mixed> $payload
     *
     * @return list<string>|null
     */
    private function extractErrorCodes(array $payload): ?array
    {
        if (
            !array_key_exists('error-codes', $payload)
            || !is_array($payload['error-codes'])
        ) {
            return null;
        }

        $errorCodes = [];

        foreach ($payload['error-codes'] as $errorCode) {
            if (!is_string($errorCode) || $errorCode === '') {
                return null;
            }

            $errorCodes[] = $errorCode;
        }

        return $errorCodes;
    }
}
