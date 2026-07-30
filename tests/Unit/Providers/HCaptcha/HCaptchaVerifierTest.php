<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\HCaptcha;

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
use WpCaptchaShield\Providers\HCaptcha\HCaptchaErrorMapper;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaResponseParser;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaVerifier;

final class HCaptchaVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private HttpClient&MockInterface $httpClient;

    private HCaptchaVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var HttpClient&MockInterface $httpClient */
        $httpClient = Mockery::mock(HttpClient::class);

        $this->httpClient = $httpClient;
        $this->verifier = $this->createVerifier('secret-key');
    }

    public function testItIdentifiesItsProvider(): void
    {
        self::assertSame(
            CaptchaProvider::HCaptcha,
            $this->verifier->provider(),
        );
    }

    public function testItRejectsAMissingTokenWithoutCallingHCaptcha(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->verifier->verify(
            $this->request('   '),
        );

        self::assertSame(VerificationStatus::Failed, $result->status());
        self::assertSame(
            VerificationFailureReason::MissingToken,
            $result->reason(),
        );
    }

    public function testItRejectsAMissingSecretWithoutCallingHCaptcha(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->createVerifier('   ')->verify(
            $this->request('submitted-token'),
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

    public function testItSendsTheExpectedVerificationRequest(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                'https://api.hcaptcha.com/siteverify',
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => 'submitted-token',
                        'remoteip' => '203.0.113.10',
                    ],
                ],
            )
            ->andReturn(new HttpResponse(200, '{"success":true}'));

        $result = $this->verifier->verify(
            $this->request(
                ' submitted-token ',
                ' 203.0.113.10 ',
                ' Mozilla/5.0 ',
                ' wordpress_login ',
            ),
        );

        self::assertTrue($result->isSuccessful());
    }

    #[DataProvider('emptyRemoteIpCases')]
    public function testItOmitsAnEmptyRemoteIp(?string $remoteIp): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                'https://api.hcaptcha.com/siteverify',
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => 'submitted-token',
                    ],
                ],
            )
            ->andReturn(new HttpResponse(200, '{"success":true}'));

        self::assertTrue(
            $this->verifier
                ->verify(
                    $this->request(
                        'submitted-token',
                        $remoteIp,
                    ),
                )
                ->isSuccessful(),
        );
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function emptyRemoteIpCases(): iterable
    {
        yield 'null' => [null];
        yield 'empty' => [''];
        yield 'whitespace' => ['   '];
    }

    public function testItIgnoresUnusedRequestContext(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                'https://api.hcaptcha.com/siteverify',
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => 'submitted-token',
                    ],
                ],
            )
            ->andReturn(new HttpResponse(200, '{"success":true}'));

        $result = $this->verifier->verify(
            $this->request(
                'submitted-token',
                userAgent: 'Mozilla/5.0',
                expectedAction: 'wordpress_login',
            ),
        );

        self::assertTrue($result->isSuccessful());
    }

    public function testItMapsAProviderRejection(): void
    {
        $this->expectResponse(
            new HttpResponse(
                200,
                '{"success":false,"error-codes":["invalid-input-response"]}',
            ),
        );

        $result = $this->verifier->verify(
            $this->request('submitted-token'),
        );

        self::assertSame(VerificationStatus::Failed, $result->status());
        self::assertSame(
            VerificationFailureReason::InvalidToken,
            $result->reason(),
        );
    }

    public function testItMapsAnHttpClientExceptionToUnavailable(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andThrow(new HttpClientException('Network failure.'));

        $result = $this->verifier->verify(
            $this->request('submitted-token'),
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

    #[DataProvider('nonSuccessfulStatusCases')]
    public function testItMapsANonSuccessfulHttpStatusToUnavailable(
        int $statusCode,
    ): void {
        $this->expectResponse(new HttpResponse($statusCode, '{}'));

        $result = $this->verifier->verify(
            $this->request('submitted-token'),
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
    public static function nonSuccessfulStatusCases(): iterable
    {
        yield 'client error' => [400];
        yield 'server error' => [500];
    }

    public function testItMapsAMalformedResponseToUnavailable(): void
    {
        $this->expectResponse(new HttpResponse(200, '{'));

        $result = $this->verifier->verify(
            $this->request('submitted-token'),
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

    private function createVerifier(string $secretKey): HCaptchaVerifier
    {
        return new HCaptchaVerifier(
            $secretKey,
            $this->httpClient,
            new HCaptchaResponseParser(),
            new HCaptchaErrorMapper(),
        );
    }

    private function request(
        string $token,
        ?string $remoteIp = null,
        ?string $userAgent = null,
        ?string $expectedAction = null,
    ): CaptchaVerificationRequest {
        return new CaptchaVerificationRequest(
            $token,
            $remoteIp,
            $userAgent,
            $expectedAction,
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
