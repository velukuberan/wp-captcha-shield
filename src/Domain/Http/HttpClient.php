<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Http;

interface HttpClient
{
    /**
     * @param array<string, mixed> $arguments
     *
     * @throws HttpClientException
     */
    public function post(
        string $url,
        array $arguments,
    ): HttpResponse;
}
