<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\Provider\GoogleRecaptchaMode;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaAssessmentParser;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaErrorMapper;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaVerifier;

final class GoogleRecaptchaInvisibleVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private HttpClient&MockInterface $httpClient;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var HttpClient&MockInterface $httpClient */
        $httpClient = Mockery::mock(HttpClient::class);
        $this->httpClient = $httpClient;
    }

    public function testItAcceptsAValidAssessmentAboveTheThreshold(): void
    {
        $this->expectAssessment(0.9, 'wordpress_login');

        $result = $this->verifier()->verify(
            new CaptchaVerificationRequest(
                'submitted-token',
                expectedAction: 'wordpress_login',
            ),
        );

        self::assertTrue($result->isSuccessful());
    }

    public function testItRejectsAScoreBelowTheThreshold(): void
    {
        $this->expectAssessment(0.49);

        $result = $this->verifier()->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationFailureReason::LowScore,
            $result->reason(),
        );
    }

    public function testItRejectsAMismatchedAction(): void
    {
        $this->expectAssessment(0.9, 'comment');

        $result = $this->verifier()->verify(
            new CaptchaVerificationRequest(
                'submitted-token',
                expectedAction: 'wordpress_login',
            ),
        );

        self::assertSame(
            VerificationFailureReason::ProviderRejected,
            $result->reason(),
        );
    }

    public function testItRejectsAValidAssessmentWithoutAScore(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn(
                new HttpResponse(
                    200,
                    '{"tokenProperties":{"valid":true}}',
                ),
            );

        $result = $this->verifier()->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationFailureReason::MalformedResponse,
            $result->reason(),
        );
    }

    private function verifier(): GoogleRecaptchaVerifier
    {
        return new GoogleRecaptchaVerifier(
            'project-id',
            'api-key',
            'site-key',
            0.5,
            GoogleRecaptchaMode::Invisible,
            $this->httpClient,
            new GoogleRecaptchaAssessmentParser(),
            new GoogleRecaptchaErrorMapper(),
        );
    }

    private function expectAssessment(
        float $score,
        ?string $action = null,
    ): void {
        $tokenProperties = ['valid' => true];

        if ($action !== null) {
            $tokenProperties['action'] = $action;
        }

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn(
                new HttpResponse(
                    200,
                    json_encode(
                        [
                            'tokenProperties' => $tokenProperties,
                            'riskAnalysis' => ['score' => $score],
                        ],
                        JSON_THROW_ON_ERROR,
                    ),
                ),
            );
    }
}
