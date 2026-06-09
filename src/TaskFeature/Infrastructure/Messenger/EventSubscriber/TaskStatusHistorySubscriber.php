<?php

declare(strict_types=1);

namespace App\TaskFeature\Infrastructure\Messenger\EventSubscriber;

use App\TaskFeature\Domain\Entity\TaskStatusHistory;
use App\TaskFeature\Domain\Event\TaskStatusChanged;
use App\TaskFeature\Domain\Repository\TaskStatusHistoryRepositoryInterface;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

final class TaskStatusHistorySubscriber
{
    public function __construct(
        private readonly TaskStatusHistoryRepositoryInterface $repository,
        private readonly Security $security,
    ) {
    }

    #[AsMessageHandler(bus: 'event.bus')]
    public function onTaskStatusChanged(TaskStatusChanged $event): void
    {
        $entry = TaskStatusHistory::record(
            id: $this->generateUuid(),
            taskId: $event->taskId,
            transitionId: $event->transitionId,
            changedBy: $this->resolveActorId(),
            changedAt: new \DateTimeImmutable(),
        );

        $this->repository->save($entry);
    }

    private function resolveActorId(): ?string
    {
        $token = $this->security->getToken();

        if ($token === null) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof SecurityUser) {
            return null;
        }

        return $user->getDomainUser()->id()->value();
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
