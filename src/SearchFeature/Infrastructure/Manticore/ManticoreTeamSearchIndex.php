<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TeamSearchIndexInterface;

final class ManticoreTeamSearchIndex implements TeamSearchIndexInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /** @param list<string> $memberIds */
    public function index(
        string $teamId,
        string $title,
        string $status,
        string $createdBy,
        \DateTimeImmutable $createdAt,
        array $memberIds,
        string $tags = '',
    ): void {
        $this->client->replace('teams', $this->numericId($teamId), [
            'team_id' => $teamId,
            'title' => $title,
            'status' => $status,
            'created_by' => $createdBy,
            'created_at' => $createdAt->getTimestamp(),
            'member_ids' => implode(' ', $memberIds),
            'tags' => $tags,
        ]);
    }

    public function delete(string $teamId): void
    {
        $this->client->delete('teams', $this->numericId($teamId));
    }

    private function numericId(string $teamId): int
    {
        return (int) hexdec(substr(sha1($teamId), 0, 15));
    }
}
