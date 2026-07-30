<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Verification;

final class CaptchaVerificationRequest
{
    private string $token;

    private ?string $remoteIp;

    private ?string $userAgent;

    private ?string $expectedAction;

    public function __construct(
        string $token,
        ?string $remoteIp = null,
        ?string $userAgent = null,
        ?string $expectedAction = null,
    ) {
        $this->token = trim($token);
        $this->remoteIp = $this->normalizeOptionalValue($remoteIp);
        $this->userAgent = $this->normalizeOptionalValue($userAgent);
        $this->expectedAction = $this->normalizeOptionalValue(
            $expectedAction,
        );
    }

    public function token(): string
    {
        return $this->token;
    }

    public function remoteIp(): ?string
    {
        return $this->remoteIp;
    }

    public function userAgent(): ?string
    {
        return $this->userAgent;
    }

    public function expectedAction(): ?string
    {
        return $this->expectedAction;
    }

    private function normalizeOptionalValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
