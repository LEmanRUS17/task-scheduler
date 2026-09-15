<?php

declare(strict_types=1);

namespace App\NotificationFeature\Infrastructure\Persistence;

use App\NotificationFeature\Domain\Entity\TelegramChat;
use App\NotificationFeature\Domain\Repository\TelegramChatRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;

final class DoctrineTelegramChatRepository implements TelegramChatRepositoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function findByUserId(string $userId): ?TelegramChat
    {
        return $this->entityManager->find(TelegramChat::class, $userId);
    }
}
