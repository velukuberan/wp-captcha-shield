<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Verification;

use LogicException;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Verification\CaptchaVerifier;
use WpCaptchaShield\Domain\Verification\CaptchaVerifierProvider;

final class CaptchaVerifierResolver implements CaptchaVerifierProvider
{
    /**
     * @var array<string, CaptchaVerifier>
     */
    private array $verifiers = [];

    /**
     * @param iterable<CaptchaVerifier> $verifiers
     */
    public function __construct(iterable $verifiers)
    {
        foreach ($verifiers as $verifier) {
            $provider = $verifier->provider();
            $providerKey = $provider->value;

            if (array_key_exists($providerKey, $this->verifiers)) {
                throw new LogicException(
                    sprintf(
                        'A CAPTCHA verifier is already registered for "%s".',
                        //phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                        $provider->value,
                    ),
                );
            }

            $this->verifiers[$providerKey] = $verifier;
        }
    }

    public function resolve(
        CaptchaProvider $provider,
    ): CaptchaVerifier {
        $verifier = $this->verifiers[$provider->value] ?? null;

        if ($verifier === null) {
            throw new LogicException(
                sprintf(
                    'No CAPTCHA verifier is registered for "%s".',
                    //phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    $provider->value,
                ),
            );
        }

        return $verifier;
    }
}
