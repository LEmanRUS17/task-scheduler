<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TagSearchRepositoryInterface;

final class ManticoreTagSearchRepository implements TagSearchRepositoryInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /**
     * Returns a page of matching tag ids owned by the given user, ordered by relevance,
     * plus the total match count.
     *
     * @return array{ids: list<string>, total: int}
     */
    public function search(string $query, string $ownerId, int $limit, int $offset): array
    {
        $bool = [
            'must' => [
                [
                    'match' => [
                        'name,description' => $query
                    ]
                ]
            ],
            'filter' => [
                [
                    'equals' => [
                        'owner_id' => $ownerId
                    ]
                ]
            ],
        ];

        $result = $this->client->search('tags', [
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
            static fn(array $hit) => (string) $hit['_source']['tag_id'],
            $hits,
        ));

        return [
            'ids' => $ids,
            'total' => (int) ($result['hits']['total'] ?? count($ids)),
        ];
    }
}
