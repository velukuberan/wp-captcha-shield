<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\HCaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\HCaptcha\HCaptchaResponseParser;

final class HCaptchaResponseParserTest extends TestCase
{
    private HCaptchaResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new HCaptchaResponseParser();
    }

    public function testItParsesASuccessfulResponse(): void
    {
        $response = $this->parser->parse('{"success":true}');

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
    public function testItRejectsMalformedResponses(string $body): void
    {
        self::assertNull($this->parser->parse($body));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedResponseCases(): iterable
    {
        yield 'invalid JSON' => ['{'];
        yield 'non-object JSON' => ['true'];
        yield 'missing success' => ['{}'];
        yield 'non-boolean success' => ['{"success":"true"}'];
        yield 'rejection without errors' => ['{"success":false}'];
        yield 'rejection with empty errors' => [
            '{"success":false,"error-codes":[]}',
        ];
        yield 'errors are not an array' => [
            '{"success":false,"error-codes":"invalid-input-response"}',
        ];
        yield 'non-string error' => [
            '{"success":false,"error-codes":[1]}',
        ];
        yield 'empty error' => [
            '{"success":false,"error-codes":[""]}',
        ];
        yield 'success with errors' => [
            '{"success":true,"error-codes":["invalid-input-response"]}',
        ];
    }
}
