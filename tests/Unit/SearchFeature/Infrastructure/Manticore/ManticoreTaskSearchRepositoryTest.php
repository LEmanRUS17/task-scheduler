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
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[],"total":0}}'): ManticoreTaskSearchRepository
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
        $this->buildRepository($body)->search('fix bug', 'user-1', null, null, 10, 0);

        $bool = $body['query']['bool'];
        $this->assertArrayHasKey('must', $bool);
        $this->assertArrayHasKey('filter', $bool);
        $this->assertSame('fix bug', $bool['must'][0]['match']['title']);
        $this->assertSame('user-1', $bool['filter'][0]['equals']['created_by']);
    }

    public function testSearchWithTeamIdAddsTeamIdFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', null, 10, 0);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame('team-1', $filter[0]['equals']['team_id']);
    }

    public function testSearchWithTeamIdDoesNotFilterByUserId(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', null, 10, 0);

        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $body['query']['bool']['filter']);
        $this->assertNotContains('created_by', $filterKeys);
    }

    public function testSearchWithStatusAddsStatusFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', null, 'open', 10, 0);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(2, $filter);
        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $filter);
        $this->assertContains('created_by', $filterKeys);
        $this->assertContains('status', $filterKeys);
    }

    public function testSearchWithTeamIdAndStatusAddsBothFilters(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix bug', 'user-1', 'team-1', 'open', 10, 0);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(2, $filter);

        $filterKeys = array_map(fn(array $f) => array_key_first($f['equals']), $filter);
        $this->assertContains('team_id', $filterKeys);
        $this->assertContains('status', $filterKeys);
    }

    public function testSearchForwardsLimitAndOffset(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix', 'user-1', null, null, 20, 40);

        $this->assertSame(20, $body['limit']);
        $this->assertSame(40, $body['offset']);
    }

    public function testSearchMapsHitsToOrderedIdsWithTotal(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'total' => 7,
                'hits' => [
                    ['_source' => ['task_id' => 'task-1', 'title' => 'Fix bug',   'status' => 'open']],
                    ['_source' => ['task_id' => 'task-2', 'title' => 'Add tests', 'status' => 'done']],
                ],
            ],
        ]);

        $body = [];
        $result = $this->buildRepository($body, $hitsJson)->search('fix', 'user-1', null, null, 10, 0);

        $this->assertSame(['task-1', 'task-2'], $result['ids']);
        $this->assertSame(7, $result['total']);
    }

    public function testSearchReturnsEmptyResultWhenNoHits(): void
    {
        $body = [];
        $result = $this->buildRepository($body)->search('nothing', 'user-1', null, null, 10, 0);

        $this->assertSame(['ids' => [], 'total' => 0], $result);
    }

    public function testSearchPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix', 'user-1', null, null, 10, 0);

        $this->assertSame('tasks', $body['index']);
    }

    public function testSearchSortsByScore(): void
    {
        $body = [];
        $this->buildRepository($body)->search('fix', 'user-1', null, null, 10, 0);

        $this->assertSame([['_score' => 'desc']], $body['sort']);
    }
}
