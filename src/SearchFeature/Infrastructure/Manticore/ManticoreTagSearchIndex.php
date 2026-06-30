<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\TagSearchIndexInterface;

final class ManticoreTagSearchIndex implements TagSearchIndexInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    public function index(
        string $tagId,
        string $name,
        string $description,
        string $ownerId,
        \DateTimeImmutable $createdAt,
    ): void {
        $this->client->replace('tags', $this->numericId($tagId), [
            'tag_id' => $tagId,
            'name' => $name,
            'description' => $description,
            'owner_id' => $ownerId,
            'created_at' => $createdAt->getTimestamp(),
        ]);
    }

    public function delete(string $tagId): void
    {
        $this->client->delete('tags', $this->numericId($tagId));
    }

    private function numericId(string $tagId): int
    {
        return (int) hexdec(substr(sha1($tagId), 0, 15));
    }
}
