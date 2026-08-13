<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Infrastructure\Manticore\ManticoreClient;
use App\SearchFeature\Infrastructure\Manticore\ManticoreUserSearchRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ManticoreUserSearchRepositoryTest extends TestCase
{
    /** @param array<string, mixed> $capturedBody */
    private function buildRepository(array &$capturedBody, string $hitsJson = '{"hits":{"hits":[],"total":0}}'): ManticoreUserSearchRepository
    {
        $httpClient = new MockHttpClient(function (string $method, string $url, array $options) use (&$capturedBody, $hitsJson) {
            $capturedBody = json_decode($options['body'] ?? '{}', true);
            return new MockResponse($hitsJson);
        });

        return new ManticoreUserSearchRepository(new ManticoreClient($httpClient, 'http://manticore:9308'));
    }

    public function testSearchInTeamMatchesNameFieldsAndScopesByTeam(): void
    {
        $body = [];
        $this->buildRepository($body)->searchInTeam('team-1', 'ivan', 10, 0);

        $must = $body['query']['bool']['must'];
        $this->assertSame('ivan', $must[0]['match']['username,firstname,lastname,midlname']);
        $this->assertSame('team-1', $must[1]['match']['team_ids']);
    }

    public function testSearchInTeamForwardsLimitAndOffset(): void
    {
        $body = [];
        $this->buildRepository($body)->searchInTeam('team-1', 'ivan', 20, 40);

        $this->assertSame(20, $body['limit']);
        $this->assertSame(40, $body['offset']);
    }

    public function testSearchInTeamPassesTableNameAsIndexInBody(): void
    {
        $body = [];
        $this->buildRepository($body)->searchInTeam('team-1', 'ivan', 10, 0);

        $this->assertSame('users', $body['index']);
    }

    public function testSearchInTeamSortsByScore(): void
    {
        $body = [];
        $this->buildRepository($body)->searchInTeam('team-1', 'ivan', 10, 0);

        $this->assertSame([['_score' => 'desc']], $body['sort']);
    }

    public function testSearchInTeamMapsHitsToOrderedIdsWithTotal(): void
    {
        $hitsJson = json_encode([
            'hits' => [
                'total' => 3,
                'hits' => [
                    ['_source' => ['user_id' => 'user-1', 'username' => 'ivan.k']],
                    ['_source' => ['user_id' => 'user-2', 'username' => 'ivan.p']],
                ],
            ],
        ]);

        $body = [];
        $result = $this->buildRepository($body, $hitsJson)->searchInTeam('team-1', 'ivan', 10, 0);

        $this->assertSame(['user-1', 'user-2'], $result['ids']);
        $this->assertSame(3, $result['total']);
    }

    public function testSearchInTeamReturnsEmptyResultWhenNoHits(): void
    {
        $body = [];
        $result = $this->buildRepository($body)->searchInTeam('team-1', 'nothing', 10, 0);

        $this->assertSame(['ids' => [], 'total' => 0], $result);
    }
}
