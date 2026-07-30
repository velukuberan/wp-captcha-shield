<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Configuration\CaptchaProvider;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Domain\Verification\CaptchaVerificationRequest;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaAssessmentParser;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaErrorMapper;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaVerifier;

final class GoogleRecaptchaVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ASSESSMENT_URL =
        'https://recaptchaenterprise.googleapis.com/v1/projects/'
        . 'project-id/assessments?key=api-key';

    private HttpClient&MockInterface $httpClient;

    private GoogleRecaptchaVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var HttpClient&MockInterface $httpClient */
        $httpClient = Mockery::mock(HttpClient::class);

        $this->httpClient = $httpClient;
        $this->verifier = $this->createVerifier();
    }

    public function testItIdentifiesItsProvider(): void
    {
        self::assertSame(
            CaptchaProvider::GoogleRecaptcha,
            $this->verifier->provider(),
        );
    }

    public function testItRejectsAMissingTokenWithoutCallingGoogle(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('   '),
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MissingToken,
            $result->reason(),
        );
    }

    #[DataProvider('missingConfigurationCases')]
    public function testItRejectsMissingConfiguration(
        string $projectId,
        string $apiKey,
        string $siteKey,
    ): void {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->createVerifier(
            projectId: $projectId,
            apiKey: $apiKey,
            siteKey: $siteKey,
        )->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MissingConfiguration,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function missingConfigurationCases(): iterable
    {
        yield 'missing project ID' => ['', 'api-key', 'site-key'];
        yield 'missing API key' => ['project-id', '', 'site-key'];
        yield 'missing site key' => ['project-id', 'api-key', ''];
    }

    #[DataProvider('invalidMinimumScoreCases')]
    public function testItRejectsAnInvalidMinimumScore(
        float $minimumScore,
    ): void {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->createVerifier(
            minimumScore: $minimumScore,
        )->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::InvalidConfiguration,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{float}>
     */
    public static function invalidMinimumScoreCases(): iterable
    {
        yield 'below zero' => [-0.1];
        yield 'above one' => [1.1];
    }

    public function testItSendsTheExpectedAssessmentRequest(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                self::ASSESSMENT_URL,
                [
                    'timeout' => 10,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode(
                        [
                            'event' => [
                                'token' => 'submitted-token',
                                'siteKey' => 'site-key',
                                'userIpAddress' => '203.0.113.10',
                                'userAgent' => 'Mozilla/5.0',
                                'expectedAction' => 'wordpress_login',
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                        | JSON_UNESCAPED_SLASHES,
                    ),
                ],
            )
            ->andReturn(
                $this->validResponse(
                    0.9,
                    'wordpress_login',
                ),
            );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest(
                ' submitted-token ',
                ' 203.0.113.10 ',
                ' Mozilla/5.0 ',
                ' wordpress_login ',
            ),
        );

        self::assertTrue($result->isSuccessful());
    }

    public function testItOmitsAbsentOptionalContext(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                self::ASSESSMENT_URL,
                [
                    'timeout' => 10,
                    'headers' => [
                        'Content-Type' => 'application/json',
                    ],
                    'body' => json_encode(
                        [
                            'event' => [
                                'token' => 'submitted-token',
                                'siteKey' => 'site-key',
                            ],
                        ],
                        JSON_THROW_ON_ERROR
                        | JSON_UNESCAPED_SLASHES,
                    ),
                ],
            )
            ->andReturn($this->validResponse(0.9));

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertTrue($result->isSuccessful());
    }

    public function testItRejectsAnInvalidAssessment(): void
    {
        $this->expectResponse(
            new HttpResponse(
                200,
                '{"tokenProperties":{"valid":false,'
                . '"invalidReason":"EXPIRED"}}',
            ),
        );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::ExpiredToken,
            $result->reason(),
        );
    }

    public function testItRejectsAMismatchedAction(): void
    {
        $this->expectResponse(
            $this->validResponse(0.9, 'comment'),
        );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest(
                'submitted-token',
                expectedAction: 'wordpress_login',
            ),
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::ProviderRejected,
            $result->reason(),
        );
    }

    public function testItRejectsAMissingActionWhenExpected(): void
    {
        $this->expectResponse($this->validResponse(0.9));

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest(
                'submitted-token',
                expectedAction: 'wordpress_login',
            ),
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::ProviderRejected,
            $result->reason(),
        );
    }

    public function testItRejectsAScoreBelowTheThreshold(): void
    {
        $this->expectResponse($this->validResponse(0.49));

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::LowScore,
            $result->reason(),
        );
    }

    public function testItAcceptsAScoreAtTheThreshold(): void
    {
        $this->expectResponse($this->validResponse(0.5));

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertTrue($result->isSuccessful());
    }

    public function testItMapsAnHttpClientExceptionToUnavailable(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Network failure.'));

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Unavailable,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::NetworkFailure,
            $result->reason(),
        );
    }

    #[DataProvider('configurationHttpStatusCases')]
    public function testItMapsConfigurationHttpStatuses(
        int $statusCode,
    ): void {
        $this->expectResponse(
            new HttpResponse($statusCode, '{}'),
        );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Misconfigured,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::InvalidConfiguration,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function configurationHttpStatusCases(): iterable
    {
        yield 'bad request' => [400];
        yield 'unauthorized' => [401];
        yield 'forbidden' => [403];
        yield 'not found' => [404];
    }

    #[DataProvider('temporaryHttpStatusCases')]
    public function testItMapsTemporaryHttpStatuses(
        int $statusCode,
    ): void {
        $this->expectResponse(
            new HttpResponse($statusCode, '{}'),
        );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Unavailable,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::NetworkFailure,
            $result->reason(),
        );
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function temporaryHttpStatusCases(): iterable
    {
        yield 'request timeout' => [408];
        yield 'rate limited' => [429];
        yield 'server error' => [500];
    }

    public function testItMapsAMalformedResponseToUnavailable(): void
    {
        $this->expectResponse(
            new HttpResponse(200, '{"tokenProperties":{}}'),
        );

        $result = $this->verifier->verify(
            new CaptchaVerificationRequest('submitted-token'),
        );

        self::assertSame(
            VerificationStatus::Unavailable,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::MalformedResponse,
            $result->reason(),
        );
    }

    private function createVerifier(
        string $projectId = 'project-id',
        string $apiKey = 'api-key',
        string $siteKey = 'site-key',
        float $minimumScore = 0.5,
    ): GoogleRecaptchaVerifier {
        return new GoogleRecaptchaVerifier(
            $projectId,
            $apiKey,
            $siteKey,
            $minimumScore,
            $this->httpClient,
            new GoogleRecaptchaAssessmentParser(),
            new GoogleRecaptchaErrorMapper(),
        );
    }

    private function validResponse(
        float $score,
        ?string $action = null,
    ): HttpResponse {
        $tokenProperties = ['valid' => true];

        if ($action !== null) {
            $tokenProperties['action'] = $action;
        }

        return new HttpResponse(
            200,
            json_encode(
                [
                    'tokenProperties' => $tokenProperties,
                    'riskAnalysis' => [
                        'score' => $score,
                    ],
                ],
                JSON_THROW_ON_ERROR,
            ),
        );
    }

    private function expectResponse(HttpResponse $response): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($response);
    }
}
