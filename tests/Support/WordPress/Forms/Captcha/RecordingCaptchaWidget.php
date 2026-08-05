<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Support\WordPress\Forms\Captcha;

use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidget;
use WpCaptchaShield\WordPress\Forms\Captcha\CaptchaWidgetContext;
use WpCaptchaShield\WordPress\Settings\PluginSettings;

final class RecordingCaptchaWidget implements CaptchaWidget
{
    public ?string $operation = null;

    public ?string $action = null;

    public ?string $formId = null;

    public ?PluginSettings $settings = null;

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
        $this->operation = 'enqueue';

        $this->record($context, $settings);
    }

    public function render(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $this->operation = 'render';

        $this->record($context, $settings);
    }

    public function tokenFieldName(PluginSettings $settings): string
    {
        $this->operation = 'tokenFieldName';
        $this->settings = $settings;

        return 'recorded-token';
    }

    private function record(
        CaptchaWidgetContext $context,
        PluginSettings $settings,
    ): void {
        $this->action = $context->action();
        $this->formId = $context->formId();
        $this->settings = $settings;
    }
}
