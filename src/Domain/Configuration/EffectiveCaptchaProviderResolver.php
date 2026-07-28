<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration;

final class EffectiveCaptchaProviderResolver
{
    public function resolve(
        GlobalCaptchaSetting $globalSetting,
        FormCaptchaSetting $formSetting,
    ): EffectiveCaptchaProvider {
        if ($formSetting->usesDefault()) {
            return $this->resolveGlobalSetting($globalSetting);
        }

        if ($formSetting->isDisabled()) {
            return EffectiveCaptchaProvider::disabled();
        }

        return EffectiveCaptchaProvider::enabled(
            $formSetting->selectedProvider(),
        );
    }

    private function resolveGlobalSetting(
        GlobalCaptchaSetting $globalSetting,
    ): EffectiveCaptchaProvider {
        $provider = $globalSetting->selectedProvider();

        if ($provider === null) {
            return EffectiveCaptchaProvider::disabled();
        }

        return EffectiveCaptchaProvider::enabled($provider);
    }
}
