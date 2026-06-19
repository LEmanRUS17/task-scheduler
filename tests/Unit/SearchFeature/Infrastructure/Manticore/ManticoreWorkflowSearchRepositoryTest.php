<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreWorkflowSearchRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreWorkflowSearchRepositoryTest extends TestCase
{
    /** @param array<string, mixed> $capturedBody */
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[]}}'): ManticoreWorkflowSearchRepository
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $hitsJson) {
            $capturedBody = json_decode($options['body'] ?? '{}', true);
            return new MockResponse($hitsJson);
        });

        return new ManticoreWorkflowSearchRepository(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    public function testSearchMatchesTitleAndDescription(): void
    {
        $body = [];
        $this->buildRepository($body)->search('flow', 'user-1', false);

        $must = $body['query']['bool']['must'];
        $this->assertSame('flow', $must[0]['match']['title,description']);
    }

    public function testSearchWithoutFiltersHasNoFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('flow', 'user-1', false);

        $this->assertArrayNotHasKey('filter', $body['query']['bool']);
    }

    public function testSearchOwnedOnlyAddsCreatedByFilter(): void
    {
        $body = [];
        $this->buildRepository($body)->search('flow', 'user-1', true);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame('user-1', $filter[0]['equals']['created_by']);
    }

    public function testSearchMapsHitsToResultArray(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'hits' => [
                    ['_source' => ['workflow_id' => 'wf-1', 'title' => 'Bug flow']],
                    ['_source' => ['workflow_id' => 'wf-2', 'title' => 'Release flow']],
                ],
            ],
        ]);

        $body = [];
        $results = $this->buildRepository($body, $hitsJson)->search('flow', 'user-1', false);

        $this->assertCount(2, $results);
        $this->assertSame('wf-1', $results[0]['workflowId']);
        $this->assertSame('Bug flow', $results[0]['title']);
        $this->assertSame('wf-2', $results[1]['workflowId']);
    }

    public function testSearchReturnsEmptyArrayWhenNoHits(): void
    {
        $body = [];
        $results = $this->buildRepository($body)->search('nothing', 'user-1', false);

        $this->assertSame([], $results);
    }

    public function testSearchPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->search('flow', 'user-1', false);

        $this->assertSame('workflows', $body['index']);
    }

    public function testSearchSortsByScoreThenCreatedAt(): void
    {
        $body = [];
        $this->buildRepository($body)->search('flow', 'user-1', false);

        $this->assertSame(
            [['_score' => 'desc'], ['created_at' => 'desc']],
            $body['sort'],
        );
    }
}
