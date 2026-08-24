<?php

declare(strict_types=1);

namespace App\SearchFeature\Infrastructure\Manticore;

use App\SearchFeature\Domain\Port\UserSearchIndexInterface;

final class ManticoreUserSearchIndex implements UserSearchIndexInterface
{
    public function __construct(private readonly ManticoreClient $client)
    {
    }

    /** @param list<string> $teamIds */
    public function index(
        string $userId,
        string $username,
        string $email,
        string $firstname,
        string $lastname,
        string $midlname,
        array $teamIds,
    ): void {
        $this->client->replace('users', $this->numericId($userId), [
            'user_id' => $userId,
            'username' => $username,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'midlname' => $midlname,
            'team_ids' => implode(' ', $teamIds),
        ]);
    }

    public function delete(string $userId): void
    {
        $this->client->delete('users', $this->numericId($userId));
    }

    private function numericId(string $userId): int
    {
        return (int) hexdec(substr(sha1($userId), 0, 15));
    }
}
