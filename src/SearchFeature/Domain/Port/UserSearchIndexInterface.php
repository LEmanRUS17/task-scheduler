<?php

declare(strict_types=1);

namespace App\SearchFeature\Domain\Port;

interface UserSearchIndexInterface
{
    /** @param list<string> $teamIds */
    public function index(
        string $userId,
        string $username,
        string $email,
        string $firstname,
        string $lastname,
        string $midlname,
        array $teamIds,
    ): void;

    public function delete(string $userId): void;
}
