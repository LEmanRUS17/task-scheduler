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
     * @return array<int, array{workflowId: string, title: string}>
     */
    public function search(string $query, string $userId, bool $ownedOnly): array
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
        ]);

        $hits = $result['hits']['hits'] ?? [];

        return array_map(
            static fn(array $hit) => [
                'workflowId' => $hit['_source']['workflow_id'],
                'title'      => $hit['_source']['title'],
            ],
            $hits,
        );
    }
}
