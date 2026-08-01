<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class RecordingCaptchaWidget implements CaptchaWidget
{
    public ?string $action = null;

    public ?string $formId = null;

    public function __construct(
        private readonly CaptchaProvider $captchaProvider,
    ) {
    }

    public function provider(): CaptchaProvider
    {
        return $this->captchaProvider;
    }

    public function enqueue(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($settings);

        $this->record($context);
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        unset($settings);

        $this->record($context);
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        unset($settings);

        return 'recorded-token';
    }

    private function record(CaptchaWidgetContext $context): void
    {
        $this->action = $context->action();
        $this->formId = $context->formId();
    }
}
