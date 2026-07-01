<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTagSearchIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTagSearchIndexTest extends TestCase
{
    /** @param list<array<string, mixed>> $requests */
    private function buildIndex(array &$requests): ManticoreTagSearchIndex
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [
                'url'  => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse('{}');
        });

        return new ManticoreTagSearchIndex(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    private function numericId(string $tagId): int
    {
        return (int) hexdec(substr(sha1($tagId), 0, 15));
    }

    public function testIndexCallsReplaceWithCorrectDocument(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $requests = [];
        $this->buildIndex($requests)->index('tag-uuid', 'urgent', 'High priority', 'user-1', $createdAt);

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/replace', $requests[0]['url']);

        $body = $requests[0]['body'];
        $this->assertSame('tags', $body['index']);
        $this->assertSame($this->numericId('tag-uuid'), $body['id']);

        $doc = $body['doc'];
        $this->assertSame('tag-uuid', $doc['tag_id']);
        $this->assertSame('urgent', $doc['name']);
        $this->assertSame('High priority', $doc['description']);
        $this->assertSame('user-1', $doc['owner_id']);
        $this->assertSame($createdAt->getTimestamp(), $doc['created_at']);
    }

    public function testIndexWithEmptyDescription(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('tag-uuid', 'urgent', '', 'user-1', new \DateTimeImmutable());

        $this->assertSame('', $requests[0]['body']['doc']['description']);
    }

    public function testNumericIdIsDeterministicForSameTagId(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('tag-uuid', 'T', '', 'user-1', $createdAt);
        $index->index('tag-uuid', 'T', '', 'user-1', $createdAt);

        $this->assertSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDifferentTagIdsProduceDifferentNumericIds(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('tag-aaa', 'T', '', 'user-1', $createdAt);
        $index->index('tag-bbb', 'T', '', 'user-1', $createdAt);

        $this->assertNotSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDeleteCallsDeleteEndpoint(): void
    {
        $requests = [];
        $this->buildIndex($requests)->delete('tag-uuid');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/delete', $requests[0]['url']);
        $this->assertSame('tags', $requests[0]['body']['index']);
        $this->assertSame($this->numericId('tag-uuid'), $requests[0]['body']['id']);
    }
}
