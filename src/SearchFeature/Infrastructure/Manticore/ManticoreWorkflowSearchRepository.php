<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\WorkflowSearchRepositoryInterface;

final class ManticoreWorkflowSearchRepository implements WorkflowSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /**
     * Returns a page of matching workflow ids, ordered by relevance, plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(string $query, string $userId, bool $ownedOnly, int $limit, int $offset): array
    {
        $must = [
            [
                'match' => [
                    'title,description' => $query
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

        $bool = ['must' => $must];

        if ($filter !== []) {
            $bool['filter'] = $filter;
        }

        $result = $this->client->search('workflows', [
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
            static fn(array $hit) => (string) $hit['_source']['workflow_id'],
            $hits,
        ));

        return [
            'ids' => $ids,
            'total' => (int) ($result['hits']['total'] ?? count($ids)),
        ];
    }
}
