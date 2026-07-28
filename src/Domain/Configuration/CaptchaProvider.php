<?php

declare(strict_types=1);

namespace WpCaptchaShield\Domain\Configuration;

enum CaptchaProvider: string
{
    case CloudflareTurnstile = 'cloudflare_turnstile';
    case GoogleCloudFraudDefense = 'google_cloud_fraud_defense';
    case HCaptcha = 'hcaptcha';
}
