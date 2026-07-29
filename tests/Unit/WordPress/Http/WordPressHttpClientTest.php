<?php

declare(strict_types=1);

namespace WpCaptchaShield\Tests\Unit\WordPress\Http;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use stdClass;
use WpCaptchaShield\Domain\Http\HttpClientException;
use WpCaptchaShield\WordPress\Http\WordPressHttpClient;

final class WordPressHttpClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();

        parent::tearDown();
    }

    public function testItReturnsACompletedHttpResponse(): void
    {
        $url = 'https://example.com/verify';
        $arguments = [
            'timeout' => 10,
            'body' => [
                'secret' => 'secret-key',
                'response' => 'submitted-token',
            ],
        ];
        $wordpressResponse = [
            'response' => [
                'code' => 200,
            ],
            'body' => '{"success":true}',
        ];

        Functions\expect('wp_remote_post')
            ->once()
            ->with($url, $arguments)
            ->andReturn($wordpressResponse);

        Functions\expect('is_wp_error')
            ->once()
            ->with($wordpressResponse)
            ->andReturn(false);

        Functions\expect('wp_remote_retrieve_response_code')
            ->once()
            ->with($wordpressResponse)
            ->andReturn(200);

        Functions\expect('wp_remote_retrieve_body')
            ->once()
            ->with($wordpressResponse)
            ->andReturn('{"success":true}');

        $response = (new WordPressHttpClient())->post(
            $url,
            $arguments,
        );

        self::assertSame(200, $response->statusCode());
        self::assertSame(
            '{"success":true}',
            $response->body(),
        );
    }

    public function testItThrowsWhenWordPressReportsAnError(): void
    {
        $wordpressError = new stdClass();

        Functions\expect('wp_remote_post')
            ->once()
            ->andReturn($wordpressError);

        Functions\expect('is_wp_error')
            ->once()
            ->with($wordpressError)
            ->andReturn(true);

        $this->expectException(HttpClientException::class);
        $this->expectExceptionMessage(
            'The WordPress HTTP request failed.',
        );

        (new WordPressHttpClient())->post(
            'https://example.com/verify',
            [],
        );
    }
}
