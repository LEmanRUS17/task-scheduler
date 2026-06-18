<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTeamSearchRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTeamSearchRepositoryTest extends TestCase
{
    /** @param array<string, mixed> $capturedBody */
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[]}}'): ManticoreTeamSearchRepository
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $hitsJson) {
            $capturedBody = json_decode($options['body'] ?? '{}', true);
            return new MockResponse($hitsJson);
        });

        return new ManticoreTeamSearchRepository(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    public function testSearchMatchesTitleAndScopesByMembership(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', [], false);

        $must = $body['query']['bool']['must'];
        $this->assertSame('backend', $must[0]['match']['title']);
        $this->assertSame('user-1', $must[1]['match']['member_ids']);
    }

    public function testSearchWithoutFiltersHasNoFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', [], false);

        $this->assertArrayNotHasKey('filter', $body['query']['bool']);
    }

    public function testSearchWithSingleStatusAddsInFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', ['active'], false);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame(['active'], $filter[0]['in']['status']);
    }

    public function testSearchWithMultipleStatusesAddsInFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', ['active', 'archived'], false);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame(['active', 'archived'], $filter[0]['in']['status']);
    }

    public function testSearchOwnedOnlyAddsCreatedByFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', [], true);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame('user-1', $filter[0]['equals']['created_by']);
    }

    public function testSearchOwnedOnlyWithStatusesAddsBothFilters(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', ['active'], true);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(2, $filter);
        $this->assertSame('user-1', $filter[0]['equals']['created_by']);
        $this->assertSame(['active'], $filter[1]['in']['status']);
    }

    public function testSearchMapsHitsToResultArray(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'hits' => [
                    ['_source' => ['team_id' => 'team-1', 'title' => 'Backend',  'status' => 'active']],
                    ['_source' => ['team_id' => 'team-2', 'title' => 'Frontend', 'status' => 'active']],
                ],
            ],
        ]);

        $body = [];
        $results = $this->buildRepository($body, $hitsJson)->search('end', 'user-1', [], false);

        $this->assertCount(2, $results);
        $this->assertSame('team-1', $results[0]['teamId']);
        $this->assertSame('Backend', $results[0]['title']);
        $this->assertSame('active', $results[0]['status']);
        $this->assertSame('team-2', $results[1]['teamId']);
    }

    public function testSearchReturnsEmptyArrayWhenNoHits(): void
    {
        $body = [];
        $results = $this->buildRepository($body)->search('nothing', 'user-1', [], false);

        $this->assertSame([], $results);
    }

    public function testSearchPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', [], false);

        $this->assertSame('teams', $body['index']);
    }

    public function testSearchSortsByScoreThenCreatedAt(): void
    {
        $body = [];
        $this->buildRepository($body)->search('backend', 'user-1', [], false);

        $this->assertSame(
            [['_score' => 'desc'], ['created_at' => 'desc']],
            $body['sort'],
        );
    }
}
