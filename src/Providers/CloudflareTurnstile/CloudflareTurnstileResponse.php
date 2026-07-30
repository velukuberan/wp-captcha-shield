<?php

declare(strict_types=1);

namespace WpCaptchaShield\Providers\CloudflareTurnstile;

use InvalidArgumentException;

final class CloudflareTurnstileResponse
{
    /**
     * @param list<string> $errorCodes
     */
    private function __construct(
        private bool $successful,
        private array $errorCodes,
    ) {
        $this->assertValidState();
    }

    public static function successful(): self
    {
        return new self(true, []);
    }

    /**
     * @param list<string> $errorCodes
     */
    public static function rejected(array $errorCodes): self
    {
        return new self(false, $errorCodes);
    }

    public function isSuccessful(): bool
    {
        return $this->successful;
    }

    /**
     * @return list<string>
     */
    public function errorCodes(): array
    {
        return $this->errorCodes;
    }

    private function assertValidState(): void
    {
        if ($this->successful && $this->errorCodes !== []) {
            throw new InvalidArgumentException(
                'A successful Turnstile response cannot contain error codes.',
            );
        }

        if (!$this->successful && $this->errorCodes === []) {
            throw new InvalidArgumentException(
                'A rejected Turnstile response must contain an error code.',
            );
        }

        foreach ($this->errorCodes as $errorCode) {
            if ($errorCode === '') {
                throw new InvalidArgumentException(
                    'A Turnstile error code cannot be empty.',
                );
            }
        }
    }
}
