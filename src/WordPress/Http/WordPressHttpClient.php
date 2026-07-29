<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Http;

use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Http\HttpResponse;

final class WordPressHttpClient implements HttpClient
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @throws HttpClientException
     */
    public function post(
        string $url,
        array $arguments,
    ): HttpResponse {
        $response = wp_remote_post($url, $arguments);

        if (is_wp_error($response)) {
            throw new HttpClientException(
                'The WordPress HTTP request failed.',
            );
        }

        return new HttpResponse(
            wp_remote_retrieve_response_code($response),
            wp_remote_retrieve_body($response),
        );
    }
}
