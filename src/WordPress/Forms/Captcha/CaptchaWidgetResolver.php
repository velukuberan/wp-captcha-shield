<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

use InvalidArgumentException;
use RuntimeException;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;

final class CaptchaWidgetResolver
{
    /**
     * @var array<string, CaptchaWidget>
     */
    private array $widgets = [];

    /**
     * @param list<CaptchaWidget> $widgets
     */
    public function __construct(array $widgets)
    {
        foreach ($widgets as $widget) {
            $provider = $widget->provider();
            $key = $provider->value;

            if (isset($this->widgets[$key])) {
                throw new InvalidArgumentException(
                    sprintf(
                        'A CAPTCHA widget is already registered for provider "%s".',
                        // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                        $key,
                    ),
                );
            }

            $this->widgets[$key] = $widget;
        }
    }

    public function resolve(CaptchaProvider $provider): CaptchaWidget
    {
        return $this->widgets[$provider->value]
            ?? throw new RuntimeException(
                sprintf(
                    'No CAPTCHA widget is registered for provider "%s".',
                    // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
                    $provider->value,
                ),
            );
    }
}
