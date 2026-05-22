<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TaskSearchRepositoryInterface;

final class ManticoreTaskSearchRepository implements TaskSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    public function search(string $query, ?string $teamId, ?string $status): array
    {
        $must = [
            [
                'match' => [
                    'title' => $query
                ]
            ]
        ];
        $filter = [];

        if ($teamId !== null) {
            $filter[] = [
                'equals' => [
                    'team_id' => $teamId
                ]
            ];
        }

        if ($status !== null) {
            $filter[] = [
                'equals' => [
                    'status' => $status
                ]
            ];
        }

        $queryBody = empty($filter)
            ? ['must' => $must]
            : ['must' => $must, 'filter' => $filter];

        $result = $this->client->search('tasks', ['query' => ['bool' => $queryBody]]);

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
