<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreTagSearchRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreTagSearchRepositoryTest extends TestCase
{
    /** @param array<string, mixed> $capturedBody */
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[],"total":0}}'): ManticoreTagSearchRepository
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $hitsJson) {
            $capturedBody = json_decode($options['body'] ?? '{}', true);
            return new MockResponse($hitsJson);
        });

        return new ManticoreTagSearchRepository(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    public function testSearchMatchesNameAndDescription(): void
    {
        $body = [];
        $this->buildRepository($body)->search('urgent', 'user-1', 10, 0);

        $must = $body['query']['bool']['must'];
        $this->assertSame('urgent', $must[0]['match']['name,description']);
    }

    public function testSearchAlwaysFiltersByOwner(): void
    {
        $body = [];
        $this->buildRepository($body)->search('urgent', 'user-1', 10, 0);

        $filter = $body['query']['bool']['filter'];
        $this->assertCount(1, $filter);
        $this->assertSame('user-1', $filter[0]['equals']['owner_id']);
    }

    public function testSearchForwardsLimitAndOffset(): void
    {
        $body = [];
        $this->buildRepository($body)->search('urgent', 'user-1', 20, 40);

        $this->assertSame(20, $body['limit']);
        $this->assertSame(40, $body['offset']);
    }

    public function testSearchMapsHitsToOrderedIdsWithTotal(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'total' => 7,
                'hits' => [
                    ['_source' => ['tag_id' => 'tag-1', 'name' => 'urgent']],
                    ['_source' => ['tag_id' => 'tag-2', 'name' => 'backend']],
                ],
            ],
        ]);

        $body = [];
        $result = $this->buildRepository($body, $hitsJson)->search('e', 'user-1', 10, 0);

        $this->assertSame(['tag-1', 'tag-2'], $result['ids']);
        $this->assertSame(7, $result['total']);
    }

    public function testSearchReturnsEmptyResultWhenNoHits(): void
    {
        $body = [];
        $result = $this->buildRepository($body)->search('nothing', 'user-1', 10, 0);

        $this->assertSame(['ids' => [], 'total' => 0], $result);
    }

    public function testSearchPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->search('urgent', 'user-1', 10, 0);

        $this->assertSame('tags', $body['index']);
    }

    public function testSearchSortsByScoreThenCreatedAt(): void
    {
        $body = [];
        $this->buildRepository($body)->search('urgent', 'user-1', 10, 0);

        $this->assertSame(
            [['_score' => 'desc'], ['created_at' => 'desc']],
            $body['sort'],
        );
    }
}
