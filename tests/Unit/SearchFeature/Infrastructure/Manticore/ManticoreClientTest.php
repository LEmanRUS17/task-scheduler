<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreClientTest extends TestCase
{
    private const BASE_URL = 'http://manticore:9308';

    private function captureRequest(array &$captured, string $responseBody = '{}'): ManticoreClient
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured, $responseBody) {
            $captured = [
                'method' => $method,
                'url'    => $url,
                'body'   => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse($responseBody);
        });

        return new ManticoreClient($httpClient, self::BASE_URL);
    }

    public function testReplaceCallsCorrectEndpointWithBody(): void
    {
        $captured = [];
        $this->captureRequest($captured)->replace('tasks', 42, ['task_id' => 'abc', 'title' => 'Fix']);

        $this->assertSame('POST', $captured['method']);
        $this->assertSame(self::BASE_URL . '/replace', $captured['url']);
        $this->assertSame('tasks', $captured['body']['index']);
        $this->assertSame(42, $captured['body']['id']);
        $this->assertSame('abc', $captured['body']['doc']['task_id']);
    }

    public function testDeleteCallsCorrectEndpointWithBody(): void
    {
        $captured = [];
        $this->captureRequest($captured)->delete('tasks', 42);

        $this->assertSame('POST', $captured['method']);
        $this->assertSame(self::BASE_URL . '/delete', $captured['url']);
        $this->assertSame('tasks', $captured['body']['index']);
        $this->assertSame(42, $captured['body']['id']);
    }

    public function testSearchReturnsDecodedResponse(): void
    {
        $responseBody = json_encode(['hits' => ['total' => 2, 'hits' => []]]);
        $captured = [];
        $result = $this->captureRequest($captured, $responseBody)
            ->search('tasks', ['query' => ['bool' => ['must' => []]]]);

        $this->assertSame(self::BASE_URL . '/search', $captured['url']);
        $this->assertSame(2, $result['hits']['total']);
    }

    public function testThrowsRuntimeExceptionWhenResponseIs4xx(): void
    {
        $client = new ManticoreClient(
            new MockHttpClient(new MockResponse('Not found', ['http_code' => 404])),
            self::BASE_URL,
        );

        $this->expectException(\RuntimeException::class);
        $client->search('tasks', []);
    }

    public function testThrowsRuntimeExceptionWhenResponseIs5xx(): void
    {
        $client = new ManticoreClient(
            new MockHttpClient(new MockResponse('Server error', ['http_code' => 500])),
            self::BASE_URL,
        );

        $this->expectException(\RuntimeException::class);
        $client->replace('tasks', 1, []);
    }

    public function testSqlCallsCorrectEndpointWithFormEncodedBody(): void
    {
        $captured = [];
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$captured) {
            $captured = ['method' => $method, 'url' => $url, 'body' => $options['body'] ?? null];
            return new MockResponse('');
        });

        (new ManticoreClient($httpClient, self::BASE_URL))->sql('CREATE TABLE tasks');

        $this->assertSame('POST', $captured['method']);
        $this->assertStringContainsString('/sql', $captured['url']);
        $this->assertStringContainsString('mode=raw', $captured['url']);
        $this->assertStringContainsString('query=', $captured['body']);
        $this->assertStringContainsString(urlencode('CREATE TABLE tasks'), $captured['body']);
    }

    public function testSqlThrowsRuntimeExceptionWhenResponseIs4xx(): void
    {
        $client = new ManticoreClient(
            new MockHttpClient(new MockResponse('error', ['http_code' => 400])),
            self::BASE_URL,
        );

        $this->expectException(\RuntimeException::class);
        $client->sql('CREATE TABLE tasks');
    }
}
