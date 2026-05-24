<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTaskSearchIndex;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTaskSearchIndexTest extends TestCase
{
    /** @param list<array<string, mixed>> $requests */
    private function buildIndex(array &$requests): ManticoreTaskSearchIndex
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$requests) {
            $requests[] = [
                'url'  => $url,
                'body' => json_decode($options['body'] ?? '{}', true),
            ];
            return new MockResponse('{}');
        });

        return new ManticoreTaskSearchIndex(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    private function numericId(string $taskId): int
    {
        return (int) hexdec(substr(sha1($taskId), 0, 15));
    }

    public function testIndexCallsReplaceWithCorrectDocument(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('task-uuid', 'Fix bug', 'normal', 'open', 'team-1', 'user-1');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/replace', $requests[0]['url']);

        $body = $requests[0]['body'];
        $this->assertSame('tasks', $body['index']);
        $this->assertSame($this->numericId('task-uuid'), $body['id']);

        $doc = $body['doc'];
        $this->assertSame('task-uuid', $doc['task_id']);
        $this->assertSame('Fix bug', $doc['title']);
        $this->assertSame('normal', $doc['priority']);
        $this->assertSame('open', $doc['status']);
        $this->assertSame('team-1', $doc['team_id']);
        $this->assertSame('user-1', $doc['created_by']);
    }

    public function testIndexWithNullTeamIdPassesEmptyString(): void
    {
        $requests = [];
        $this->buildIndex($requests)->index('task-uuid', 'Fix bug', 'normal', 'open', null, 'user-1');

        $this->assertSame('', $requests[0]['body']['doc']['team_id']);
    }

    public function testNumericIdIsDeterministicForSameTaskId(): void
    {
        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('task-uuid', 'T', 'normal', 'open', null, 'user-1');
        $index->index('task-uuid', 'T', 'normal', 'open', null, 'user-1');

        $this->assertSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDifferentTaskIdsProduceDifferentNumericIds(): void
    {
        $requests = [];
        $index = $this->buildIndex($requests);
        $index->index('task-aaa', 'T', 'normal', 'open', null, 'user-1');
        $index->index('task-bbb', 'T', 'normal', 'open', null, 'user-1');

        $this->assertNotSame($requests[0]['body']['id'], $requests[1]['body']['id']);
    }

    public function testDeleteCallsDeleteEndpoint(): void
    {
        $requests = [];
        $this->buildIndex($requests)->delete('task-uuid');

        $this->assertCount(1, $requests);
        $this->assertStringContainsString('/delete', $requests[0]['url']);
        $this->assertSame('tasks', $requests[0]['body']['index']);
        $this->assertSame($this->numericId('task-uuid'), $requests[0]['body']['id']);
    }
}
