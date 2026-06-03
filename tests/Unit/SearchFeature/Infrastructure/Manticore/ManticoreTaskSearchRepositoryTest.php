<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTaskSearchRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTaskSearchRepositoryTest extends TestCase
{
    /** @param array<string, mixed> $capturedBody */
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[]}}'): ManticoreTaskSearchRepository
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $hitsJson) {
            $capturedBody = json_decode($options['body'] ?? '{}', true);
            return new MockResponse($hitsJson);
        });

        return new ManticoreTaskSearchRepository(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    public function testSearchWithoutTeamIdFiltersbyUserId(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', null, null);

        $bool = $body['query']['bool'];
        $this->assertArrayHasKey('must', $bool);
        $this->assertArrayHasKey('filter', $bool);
        $this->assertSame('fix bug', $bool['must'][0]['match']['title']);
        $this->assertSame('user-1', $bool['filter'][0]['equals']['created_by']);
    }

    public function testSearchWithTeamIdAddsTeamIdFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', null);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame('team-1', $filter[0]['equals']['team_id']);
    }

    public function testSearchWithTeamIdDoesNotFilterByUserId(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', null);

        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $body['query']['bool']['filter']);
        $this->assertNotContains('created_by', $filterKeys);
    }

    public function testSearchWithStatusAddsStatusFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', null, 'open');

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(2, $filter);
        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $filter);
        $this->assertContains('created_by', $filterKeys);
        $this->assertContains('status', $filterKeys);
    }

    public function testSearchWithTeamIdAndStatusAddsBothFilters(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', 'open');

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(2, $filter);

        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $filter);
        $this->assertContains('team_id', $filterKeys);
        $this->assertContains('status', $filterKeys);
    }

    public function testSearchMapsHitsToResultArray(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'hits' => [
                    ['_source' => ['task_id' => 'task-1', 'title' => 'Fix bug',   'status' => 'open']],
                    ['_source' => ['task_id' => 'task-2', 'title' => 'Add tests', 'status' => 'done']],
                ],
            ],
        ]);

        $body = [];
        $results = $this->buildRepository($body, $hitsJson)->search('fix', 'user-1', null, null);

        $this->assertCount(2, $results);
        $this->assertSame('task-1', $results[0]['taskId']);
        $this->assertSame('Fix bug', $results[0]['title']);
        $this->assertSame('open', $results[0]['status']);
        $this->assertSame('task-2', $results[1]['taskId']);
    }

    public function testSearchReturnsEmptyArrayWhenNoHits(): void
    {
        $body = [];
        $results = $this->buildRepository($body)->search('nothing', 'user-1', null, null);

        $this->assertSame([], $results);
    }

    public function testSearchPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix', 'user-1', null, null);

        $this->assertSame('tasks', $body['index']);
    }
}
