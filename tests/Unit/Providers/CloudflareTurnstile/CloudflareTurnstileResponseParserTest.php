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

    public function testItParsesASuccessfulResponseWithAnAction(): void
    {
        $response = $this->parser->parse(
            '{"success":true,"error-codes":[],"action":"wordpress_login"}',
        );

        self::assertNotNull($response);
        self::assertTrue($response->isSuccessful());
        self::assertSame([], $response->errorCodes());
        self::assertSame('wordpress_login', $response->action());
    }

    public function testItParsesASuccessfulResponseWithoutAnAction(): void
    {
        $response = $this->parser->parse(
            '{"success":true}',
        );

        self::assertNotNull($response);
        self::assertTrue($response->isSuccessful());
        self::assertNull($response->action());
    }

    public function testItNormalizesAnEmptyActionToNull(): void
    {
        $response = $this->parser->parse(
            '{"success":true,"action":"   "}',
        );

        self::assertNotNull($response);
        self::assertNull($response->action());
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
        self::assertNull($response->action());
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
        yield 'invalid JSON' => ['{"success":'];
        yield 'non-object JSON' => ['"successful"'];
        yield 'missing success' => ['{"error-codes":[]}'];
        yield 'non-boolean success' => [
            '{"success":"true","error-codes":[]}',
        ];
        yield 'success with errors' => [
            '{"success":true,"error-codes":["internal-error"]}',
        ];
        yield 'success with non-array errors' => [
            '{"success":true,"error-codes":"internal-error"}',
        ];
        yield 'success with non-string error' => [
            '{"success":true,"error-codes":[123]}',
        ];
        yield 'success with empty error' => [
            '{"success":true,"error-codes":[""]}',
        ];
        yield 'success with non-string action' => [
            '{"success":true,"action":123}',
        ];
        yield 'failure without errors' => ['{"success":false}'];
        yield 'failure with empty errors' => [
            '{"success":false,"error-codes":[]}',
        ];
        yield 'failure with non-array errors' => [
            '{"success":false,"error-codes":"bad-request"}',
        ];
        yield 'failure with non-string error' => [
            '{"success":false,"error-codes":[123]}',
        ];
        yield 'failure with empty error' => [
            '{"success":false,"error-codes":[""]}',
        ];
    }
}
