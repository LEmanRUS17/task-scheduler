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
     * @param list<string> $statuses
     * @return array<int, array{teamId: string, title: string, status: string}>
     */
    public function search(string $query, string $userId, array $statuses, bool $ownedOnly): array
    {
        $must = [
            [
                'match' => [
                    'title' => $query
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
        ]);

        $hits = $result['hits']['hits'] ?? [];

        return array_map(
            static fn(array $hit) => [
                'teamId' => $hit['_source']['team_id'],
                'title'  => $hit['_source']['title'],
                'status' => $hit['_source']['status'],
            ],
            $hits,
        );
    }
}
