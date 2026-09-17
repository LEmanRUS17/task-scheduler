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

    public function findByChatId(int $chatId): ?TelegramChat
    {
        return $this->entityManager->getRepository(TelegramChat::class)->findOneBy(['chatId' => $chatId]);
    }

    public function isLinkedForUserId(string $userId): bool
    {
        return $this->entityManager->createQueryBuilder()
            ->select('1')
            ->from(TelegramChat::class, 'chat')
            ->where('chat.userId = :userId')
            ->setParameter('userId', $userId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult() !== null;
    }

    public function save(TelegramChat $chat): void
    {
        $this->entityManager->persist($chat);
        $this->entityManager->flush();
    }
}
