<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\CloudflareTurnstile;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\CloudflareTurnstile\CloudflareTurnstileResponseParser;

final class CloudflareTurnstileResponseParserTest extends TestCase
{
    private CloudflareTurnstileResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CloudflareTurnstileResponseParser();
    }

    public function testItParsesASuccessfulResponse(): void
    {
        $response = $this->parser->parse(
            '{"success":true,"error-codes":[]}',
        );

        self::assertNotNull($response);
        self::assertTrue($response->isSuccessful());
        self::assertSame([], $response->errorCodes());
    }

    public function testItParsesARejectedResponse(): void
    {
        $response = $this->parser->parse(
            '{"success":false,"error-codes":["invalid-input-response"]}',
        );

        self::assertNotNull($response);
        self::assertFalse($response->isSuccessful());
        self::assertSame(
            ['invalid-input-response'],
            $response->errorCodes(),
        );
    }

    #[DataProvider('malformedResponseCases')]
    public function testItReturnsNullForAMalformedResponse(
        string $responseBody,
    ): void {
        self::assertNull(
            $this->parser->parse($responseBody),
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedResponseCases(): iterable
    {
        yield 'invalid JSON' => [
            '{"success":',
        ];

        yield 'non-object JSON' => [
            '"successful"',
        ];

        yield 'missing success' => [
            '{"error-codes":[]}',
        ];

        yield 'non-boolean success' => [
            '{"success":"true","error-codes":[]}',
        ];

        yield 'success without error codes' => [
            '{"success":true}',
        ];

        yield 'success with errors' => [
            '{"success":true,"error-codes":["internal-error"]}',
        ];

        yield 'failure without errors' => [
            '{"success":false}',
        ];

        yield 'failure with empty errors' => [
            '{"success":false,"error-codes":[]}',
        ];

        yield 'non-array errors' => [
            '{"success":false,"error-codes":"bad-request"}',
        ];

        yield 'non-string error' => [
            '{"success":false,"error-codes":[123]}',
        ];

        yield 'empty error' => [
            '{"success":false,"error-codes":[""]}',
        ];
    }
}
