<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Http;

use InvalidArgumentException;

final class HttpResponse
{
    public function __construct(
        private int $statusCode,
        private string $body,
    ) {
        if ($this->statusCode < 100 || $this->statusCode > 599) {
            throw new InvalidArgumentException(
                'The HTTP status code must be between 100 and 599.',
            );
        }
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): string
    {
        return $this->body;
    }
}
