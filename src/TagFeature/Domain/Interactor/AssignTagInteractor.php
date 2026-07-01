<?php

declare(strict_types=1);

namespace App\TagFeature\Domain\Interactor;

use App\TagFeature\Domain\Entity\TagAssignment;
use App\TagFeature\Domain\Port\ClockInterface;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TaggableType;

final class AssignTagInteractor
{
    public function __construct(
        private readonly TagRepositoryInterface $tags,
        private readonly TagAssignmentRepositoryInterface $assignments,
        private readonly DomainEventDispatcherInterface $eventDispatcher,
        private readonly ClockInterface $clock,
    ) {
    }

    public function assign(
        TagId $tagId,
        TaggableType $entityType,
        string $entityId,
        string $assignedBy,
    ): TagAssignment {
        if ($this->tags->findById($tagId) === null) {
            throw new \DomainException(sprintf('Tag "%s" not found', $tagId->value()));
        }

        $existing = $this->assignments->find($tagId, $entityType, $entityId);
        if ($existing !== null) {
            return $existing;
        }

        $assignment = TagAssignment::create(
            $this->generateUuid(),
            $tagId,
            $entityType,
            $entityId,
            $assignedBy,
            $this->clock->now(),
        );

        $this->assignments->save($assignment);
        $this->eventDispatcher->dispatch(...$assignment->pullDomainEvents());

        return $assignment;
    }

    private function generateUuid(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr(ord($bytes[6]) & 0x0f | 0x40);
        $bytes[8] = chr(ord($bytes[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }
}
