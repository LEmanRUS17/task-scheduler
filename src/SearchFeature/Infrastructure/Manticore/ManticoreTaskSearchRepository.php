<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;

final class ManticoreTaskSearchRepository implements TaskSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /** @return array<int, array{taskId: string, title: string, status: string}> */
    public function search(string $query, string $userId, ?string $teamId, ?string $status): array
    {
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

        $result = $this->client->search('tasks', ['query' => ['bool' => ['must' => $must, 'filter' => $filter]]]);

        $hits = $result['hits']['hits'] ?? [];

        return array_map(
            static fn(array $hit) => [
                'taskId' => $hit['_source']['task_id'],
                'title'  => $hit['_source']['title'],
                'status' => $hit['_source']['status'],
            ],
            $hits,
        );
    }
}
