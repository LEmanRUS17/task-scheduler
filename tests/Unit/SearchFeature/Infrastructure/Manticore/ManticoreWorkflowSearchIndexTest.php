<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreWorkflowSearchIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreWorkflowSearchIndexTest extends TestCase
{
    /** @param list<array<string, mixed>> $requests */
    private function buildIndex(array &$requests): ManticoreWorkflowSearchIndex
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [
                'url'  => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse('{}');
        });

        return new ManticoreWorkflowSearchIndex(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    private function numericId(string $workflowId): int
    {
        return (int) hexdec(substr(sha1($workflowId), 0, 15));
    }

    public function testIndexCallsReplaceWithCorrectDocument(): void
    {
        $createdAt = new \DateTimeImmutable('2024-01-01 13:00:00');

        $requests = [];
        $this->buildIndex($requests)->index('wf-uuid', 'Bug flow', 'Handles bugs', 'user-1', $createdAt);

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/replace', $requests[0]['url']);

        $body = $requests[0]['body'];
        $this->assertSame('workflows', $body['index']);
        $this->assertSame($this->numericId('wf-uuid'), $body['id']);

        $doc = $body['doc'];
        $this->assertSame('wf-uuid', $doc['workflow_id']);
        $this->assertSame('Bug flow', $doc['title']);
        $this->assertSame('Handles bugs', $doc['description']);
        $this->assertSame('user-1', $doc['created_by']);
        $this->assertSame($createdAt->getTimestamp(), $doc['created_at']);
    }

    public function testIndexWithEmptyDescription(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('wf-uuid', 'Bug flow', '', 'user-1', new \DateTimeImmutable());

        $this->assertSame('', $requests[0]['body']['doc']['description']);
    }

    public function testNumericIdIsDeterministicForSameWorkflowId(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('wf-uuid', 'T', '', 'user-1', $createdAt);
        $index->index('wf-uuid', 'T', '', 'user-1', $createdAt);

        $this->assertSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDifferentWorkflowIdsProduceDifferentNumericIds(): void
    {
        $createdAt = new \DateTimeImmutable();

        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('wf-aaa', 'T', '', 'user-1', $createdAt);
        $index->index('wf-bbb', 'T', '', 'user-1', $createdAt);

        $this->assertNotSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDeleteCallsDeleteEndpoint(): void
    {
        $requests = [];
        $this->buildIndex($requests)->delete('wf-uuid');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/delete', $requests[0]['url']);
        $this->assertSame('workflows', $requests[0]['body']['index']);
        $this->assertSame($this->numericId('wf-uuid'), $requests[0]['body']['id']);
    }
}
