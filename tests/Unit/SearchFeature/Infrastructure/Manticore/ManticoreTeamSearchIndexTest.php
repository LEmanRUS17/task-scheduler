<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTeamSearchIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTeamSearchIndexTest extends TestCase
{
    /** @param list<array<string, mixed>> $requests */
    private function buildIndex(array &$requests): ManticoreTeamSearchIndex
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [
                'url'  => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse('{}');
        });

        return new ManticoreTeamSearchIndex(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    private function numericId(string $teamId): int
    {
        return (int) hexdec(substr(sha1($teamId), 0, 15));
    }

    public function testIndexCallsReplaceWithCorrectDocument(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $requests = [];
        $this->buildIndex($requests)->index('team-uuid', 'Backend', 'active', 'user-1', $createdAt, ['user-1', 'user-2']);

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/replace', $requests[0]['url']);

        $body = $requests[0]['body'];
        $this->assertSame('teams', $body['index']);
        $this->assertSame($this->numericId('team-uuid'), $body['id']);

        $doc = $body['doc'];
        $this->assertSame('team-uuid', $doc['team_id']);
        $this->assertSame('Backend', $doc['title']);
        $this->assertSame('active', $doc['status']);
        $this->assertSame('user-1', $doc['created_by']);
        $this->assertSame($createdAt->getTimestamp(), $doc['created_at']);
        $this->assertSame('user-1 user-2', $doc['member_ids']);
    }

    public function testIndexWithNoMembersPassesEmptyString(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('team-uuid', 'Backend', 'active', '', new \DateTimeImmutable(), []);

        $this->assertSame('', $requests[0]['body']['doc']['member_ids']);
    }

    public function testNumericIdIsDeterministicForSameTeamId(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('team-uuid', 'T', 'active', 'user-1', $createdAt, []);
        $index->index('team-uuid', 'T', 'active', 'user-1', $createdAt, []);

        $this->assertSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDifferentTeamIdsProduceDifferentNumericIds(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('team-aaa', 'T', 'active', 'user-1', $createdAt, []);
        $index->index('team-bbb', 'T', 'active', 'user-1', $createdAt, []);

        $this->assertNotSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDeleteCallsDeleteEndpoint(): void
    {
        $requests = [];
        $this->buildIndex($requests)->delete('team-uuid');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/delete', $requests[0]['url']);
        $this->assertSame('teams', $requests[0]['body']['index']);
        $this->assertSame($this->numericId('team-uuid'), $requests[0]['body']['id']);
    }
}
