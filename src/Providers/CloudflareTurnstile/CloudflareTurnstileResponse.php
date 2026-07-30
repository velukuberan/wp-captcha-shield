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
        private ?string $action,
    ) {
        $this->assertValidState();
    }

    public static function successful(
        ?string $action = null,
    ): self {
        return new self(
            true,
            [],
            self::normalizeAction($action),
        );
    }

    /**
     * @param list<string> $errorCodes
     */
    public static function rejected(array $errorCodes): self
    {
        return new self(false, $errorCodes, null);
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

    public function action(): ?string
    {
        return $this->action;
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

        if (!$this->successful && $this->action !== null) {
            throw new InvalidArgumentException(
                'A rejected Turnstile response cannot contain an action.',
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

    private static function normalizeAction(?string $action): ?string
    {
        if ($action === null) {
            return null;
        }

        $action = trim($action);

        return $action === '' ? null : $action;
    }
}
