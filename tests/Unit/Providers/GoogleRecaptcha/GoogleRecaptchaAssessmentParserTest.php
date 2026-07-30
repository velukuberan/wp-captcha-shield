<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\Providers\GoogleRecaptcha;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use WpCaptchaShield\Providers\GoogleRecaptcha\GoogleRecaptchaAssessmentParser;

final class GoogleRecaptchaAssessmentParserTest extends TestCase
{
    private GoogleRecaptchaAssessmentParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new GoogleRecaptchaAssessmentParser();
    }

    public function testItParsesAValidAssessment(): void
    {
        $assessment = $this->parser->parse(
            <<<'JSON'
            {
                "tokenProperties": {
                    "valid": true,
                    "action": "wordpress_login"
                },
                "riskAnalysis": {
                    "score": 0.9,
                    "reasons": ["AUTOMATION"]
                },
                "futureField": "ignored"
            }
            JSON,
        );

        self::assertNotNull($assessment);
        self::assertTrue($assessment->isValid());
        self::assertSame(0.9, $assessment->score());
        self::assertSame(
            'wordpress_login',
            $assessment->action(),
        );
    }

    public function testItParsesAValidAssessmentWithoutAnAction(): void
    {
        $assessment = $this->parser->parse(
            <<<'JSON'
            {
                "tokenProperties": {
                    "valid": true
                },
                "riskAnalysis": {
                    "score": 1
                }
            }
            JSON,
        );

        self::assertNotNull($assessment);
        self::assertTrue($assessment->isValid());
        self::assertSame(1.0, $assessment->score());
        self::assertNull($assessment->action());
    }

    public function testItParsesAnInvalidAssessment(): void
    {
        $assessment = $this->parser->parse(
            <<<'JSON'
            {
                "tokenProperties": {
                    "valid": false,
                    "invalidReason": "EXPIRED"
                }
            }
            JSON,
        );

        self::assertNotNull($assessment);
        self::assertFalse($assessment->isValid());
        self::assertSame(
            'EXPIRED',
            $assessment->invalidReason(),
        );
    }

    #[DataProvider('malformedResponseCases')]
    public function testItRejectsAMalformedResponse(
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
        yield 'invalid JSON' => ['{'];
        yield 'non-object JSON' => ['true'];
        yield 'missing token properties' => [
            '{"riskAnalysis":{"score":0.9}}',
        ];
        yield 'missing valid flag' => [
            '{"tokenProperties":{}}',
        ];
        yield 'non-boolean valid flag' => [
            '{"tokenProperties":{"valid":"true"}}',
        ];
        yield 'invalid token without reason' => [
            '{"tokenProperties":{"valid":false}}',
        ];
        yield 'invalid token with empty reason' => [
            '{"tokenProperties":{"valid":false,"invalidReason":""}}',
        ];
        yield 'valid token without risk analysis' => [
            '{"tokenProperties":{"valid":true}}',
        ];
        yield 'valid token without score' => [
            '{"tokenProperties":{"valid":true},"riskAnalysis":{}}',
        ];
        yield 'non-numeric score' => [
            '{"tokenProperties":{"valid":true},"riskAnalysis":{"score":"0.9"}}',
        ];
        yield 'score below zero' => [
            '{"tokenProperties":{"valid":true},"riskAnalysis":{"score":-0.1}}',
        ];
        yield 'score above one' => [
            '{"tokenProperties":{"valid":true},"riskAnalysis":{"score":1.1}}',
        ];
        yield 'non-string action' => [
            '{"tokenProperties":{"valid":true,"action":123},"riskAnalysis":{"score":0.9}}',
        ];
    }
}
