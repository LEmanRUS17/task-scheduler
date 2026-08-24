<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreUserSearchIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreUserSearchIndexTest extends TestCase
{
    /** @param list<array<string, mixed>> $requests */
    private function buildIndex(array &$requests): ManticoreUserSearchIndex
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [
                'url'  => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse('{}');
        });

        return new ManticoreUserSearchIndex(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    private function numericId(string $userId): int
    {
        return (int) hexdec(substr(sha1($userId), 0, 15));
    }

    public function testIndexCallsReplaceWithCorrectDocument(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index(
            'user-uuid',
            'john_doe',
            'john@example.com',
            'John',
            'Doe',
            'Michael',
            ['team-1', 'team-2'],
        );

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/replace', $requests[0]['url']);

        $body = $requests[0]['body'];
        $this->assertSame('users', $body['index']);
        $this->assertSame($this->numericId('user-uuid'), $body['id']);

        $doc = $body['doc'];
        $this->assertSame('user-uuid', $doc['user_id']);
        $this->assertSame('john_doe', $doc['username']);
        $this->assertSame('john@example.com', $doc['email']);
        $this->assertSame('John', $doc['firstname']);
        $this->assertSame('Doe', $doc['lastname']);
        $this->assertSame('Michael', $doc['midlname']);
        $this->assertSame('team-1 team-2', $doc['team_ids']);
    }

    public function testIndexWithNoTeamsPassesEmptyString(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('user-uuid', 'john_doe', 'john@example.com', 'John', 'Doe', '', []);

        $this->assertSame('', $requests[0]['body']['doc']['team_ids']);
    }

    public function testNumericIdIsDeterministicForSameUserId(): void
    {
        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('user-uuid', 'a', 'a@example.com', '', '', '', []);
        $index->index('user-uuid', 'a', 'a@example.com', '', '', '', []);

        $this->assertSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDifferentUserIdsProduceDifferentNumericIds(): void
    {
        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('user-aaa', 'a', 'a@example.com', '', '', '', []);
        $index->index('user-bbb', 'b', 'b@example.com', '', '', '', []);

        $this->assertNotSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDeleteCallsDeleteEndpoint(): void
    {
        $requests = [];
        $this->buildIndex($requests)->delete('user-uuid');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/delete', $requests[0]['url']);
        $this->assertSame('users', $requests[0]['body']['index']);
        $this->assertSame($this->numericId('user-uuid'), $requests[0]['body']['id']);
    }
}
