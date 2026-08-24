<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\UserSearchRepositoryInterface;

final class ManticoreUserSearchRepository implements UserSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /** @return array{ids: list<string>, total: int} */
    public function searchInTeam(string $teamId, string $query, int $limit, int $offset): array
    {
        $result = $this->client->search('users', [
            'query' => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'username,firstname,lastname,midlname' => $query
                            ]
                        ],
                        [
                            'match' => [
                                'team_ids' => $teamId
                            ]
                        ],
                    ],
                ],
            ],
            'sort' => [
                ['_score' => 'desc'],
            ],
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $hits = $result['hits']['hits'] ?? [];

        $ids = array_values(array_map(
            static fn(array $hit) => (string) $hit['_source']['user_id'],
            $hits,
        ));

        return [
            'ids' => $ids,
            'total' => (int) ($result['hits']['total'] ?? count($ids)),
        ];
    }
}
