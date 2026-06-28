<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TeamSearchRepositoryInterface;

final class ManticoreTeamSearchRepository implements TeamSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /**
     * Returns a page of matching team ids, ordered by relevance, plus the total match count.
     *
     * @param list<string> $statuses
     * @return array{ids: list<string>, total: int}
     */
    public function search(
        string $query,
        string $userId,
        array $statuses,
        bool $ownedOnly,
        int $limit,
        int $offset,
    ): array {
        $must = [
            [
                'match' => [
                    'title,tags' => $query
                ]
            ],
            [
                'match' => [
                    'member_ids' => $userId
                ]
            ]
        ];

        $filter = [];

        if ($ownedOnly) {
            $filter[] = [
                'equals' => [
                    'created_by' => $userId
                ]
            ];
        }

        if ($statuses !== []) {
            $filter[] = [
                'in' => [
                    'status' => $statuses
                ]
            ];
        }

        $bool = ['must' => $must];

        if ($filter !== []) {
            $bool['filter'] = $filter;
        }

        $result = $this->client->search('teams', [
            'query' => ['bool' => $bool],
            'sort' => [
                ['_score' => 'desc'],
                ['created_at' => 'desc'],
            ],
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $hits = $result['hits']['hits'] ?? [];

        $ids = array_values(array_map(
            static fn(array $hit) => (string) $hit['_source']['team_id'],
            $hits,
        ));

        return [
            'ids' => $ids,
            'total' => (int) ($result['hits']['total'] ?? count($ids)),
        ];
    }
}
