<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Support\Http;

use LogicException;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpResponse;

final class NonCallingHttpClient implements HttpClient
{
    /**
     * @param array<string, mixed> $arguments
     */
    public function post(
        string $url,
        array $arguments,
    ): HttpResponse {
        throw new LogicException(
            'HTTP must not be called by this test.',
        );
    }
}
