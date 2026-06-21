<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;

final class ManticoreTaskSearchRepository implements TaskSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /**
     * Returns a page of matching task ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(
        string $query,
        string $userId,
        ?string $teamId,
        ?string $status,
        int $limit,
        int $offset,
    ): array {
        $must = [
            [
                'match' => [
                    'title' => $query
                ]
            ]
        ];

        $filter = $teamId !== null
            ? [['equals' => ['team_id' => $teamId]]]
            : [['equals' => ['created_by' => $userId]]];

        if ($status !== null) {
            $filter[] = [
                'equals' => [
                    'status' => $status
                ]
            ];
        }

        $result = $this->client->search('tasks', [
            'query' => ['bool' => ['must' => $must, 'filter' => $filter]],
            'sort' => [
                ['_score' => 'desc'],
            ],
            'limit' => $limit,
            'offset' => $offset,
        ]);

        $hits = $result['hits']['hits'] ?? [];

        $ids = array_values(array_map(
            static fn(array $hit) => (string) $hit['_source']['task_id'],
            $hits,
        ));

        return [
            'ids' => $ids,
            'total' => (int) ($result['hits']['total'] ?? count($ids)),
        ];
    }
}
