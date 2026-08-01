<?php

declare(strict_types=1);

namespace WpCaptchaShield\WordPress\Forms\Captcha;

final class CaptchaWidgetContext
{
    public function __construct(
        private readonly string $action,
        private readonly string $formId,
    ) {
    }

    public function action(): string
    {
        return $this->action;
    }

    public function formId(): string
    {
        return $this->formId;
    }
}
