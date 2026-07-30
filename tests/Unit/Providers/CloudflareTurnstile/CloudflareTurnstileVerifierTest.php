<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\CloudflareTurnstile;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Domain\Http\HttpClient;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\Domain\Http\HttpResponse;
use WpCaptchaShield\Domain\Verification\VerificationFailureReason;
use WpCaptchaShield\Domain\Verification\VerificationStatus;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileErrorMapper;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileResponseParser;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileVerifier;

final class CloudflareTurnstileVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SITEVERIFY_URL =
        'https://challenges.cloudflare.com/turnstile/v0/siteverify';

    private HttpClient&MockInterface $httpClient;

    private CloudflareTurnstileVerifier $verifier;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var HttpClient&MockInterface $httpClient */
        $httpClient = Mockery::mock(HttpClient::class);

        $this->httpClient = $httpClient;
        $this->verifier = new CloudflareTurnstileVerifier(
            $this->httpClient,
            new CloudflareTurnstileResponseParser(),
            new CloudflareTurnstileErrorMapper(),
        );
    }

    public function testItRejectsAMissingTokenWithoutCallingCloudflare(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->verifier->verify(
            '   ',
            'secret-key',
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

    public function testItRejectsAnOversizedTokenWithoutCallingCloudflare(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->verifier->verify(
            str_repeat('a', 2049),
            'secret-key',
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
        self::assertSame(
            VerificationFailureReason::InvalidToken,
            $result->reason(),
        );
    }

    public function testItAcceptsATokenAtTheMaximumLength(): void
    {
        $token = str_repeat('a', 2048);

        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                self::SITEVERIFY_URL,
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => $token,
                    ],
                ],
            )
            ->andReturn(
                new HttpResponse(
                    200,
                    '{"success":true}',
                ),
            );

        $result = $this->verifier->verify(
            $token,
            'secret-key',
        );

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    public function testItRejectsAMissingSecretWithoutCallingCloudflare(): void
    {
        $this->httpClient->shouldNotReceive('post');

        $result = $this->verifier->verify(
            'submitted-token',
            '   ',
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

    public function testItReturnsSuccessfulForAValidCloudflareResponse(): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                self::SITEVERIFY_URL,
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => 'submitted-token',
                        'remoteip' => '203.0.113.10',
                    ],
                ],
            )
            ->andReturn(
                new HttpResponse(
                    200,
                    '{"success":true,"error-codes":[]}',
                ),
            );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
            ' 203.0.113.10 ',
        );

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    #[DataProvider('emptyRemoteIpCases')]
    public function testItOmitsAnEmptyRemoteIp(
        ?string $remoteIp,
    ): void {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->with(
                self::SITEVERIFY_URL,
                [
                    'timeout' => 10,
                    'body' => [
                        'secret' => 'secret-key',
                        'response' => 'submitted-token',
                    ],
                ],
            )
            ->andReturn(
                new HttpResponse(
                    200,
                    '{"success":true}',
                ),
            );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
            $remoteIp,
        );

        self::assertTrue($result->isSuccessful());
        self::assertNull($result->reason());
    }

    /**
     * @return iterable<string, array{?string}>
     */
    public static function emptyRemoteIpCases(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'whitespace' => ['   '];
    }

    public function testItMapsACloudflareRejection(): void
    {
        $this->expectResponse(
            new HttpResponse(
                200,
                '{"success":false,"error-codes":["invalid-input-response"]}',
            ),
        );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
        );

        self::assertSame(
            VerificationStatus::Failed,
            $result->status(),
        );
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
            ->andThrow(
                new HttpClientException(
                    'The request failed.',
                ),
            );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
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

    public function testItMapsANonSuccessfulHttpStatusToUnavailable(): void
    {
        $this->expectResponse(
            new HttpResponse(
                503,
                '{"success":false,"error-codes":["internal-error"]}',
            ),
        );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
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

    public function testItMapsAMalformedResponseToUnavailable(): void
    {
        $this->expectResponse(
            new HttpResponse(
                200,
                '{"success":',
            ),
        );

        $result = $this->verifier->verify(
            'submitted-token',
            'secret-key',
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

    private function expectResponse(HttpResponse $response): void
    {
        $this->httpClient
            ->shouldReceive('post')
            ->once()
            ->andReturn($response);
    }
}
