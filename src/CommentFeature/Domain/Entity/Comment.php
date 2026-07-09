<?php

declare(strict_types=1);

namespace App\CommentFeature\Domain\Entity;

use App\CommentFeature\Domain\Event\CommentAdded;
use App\CommentFeature\Domain\Event\CommentDeleted;
use App\CommentFeature\Domain\Event\CommentUpdated;
use App\CommentFeature\Domain\ValueObject\CommentableType;
use App\CommentFeature\Domain\ValueObject\CommentContent;
use App\CommentFeature\Domain\ValueObject\CommentId;

final class Comment
{
    private string $id;
    private string $entityType;
    private string $entityId;
    private string $authorId;
    private string $content;
    private ?string $parentId;
    private \DateTimeImmutable $createdAt;
    private ?\DateTimeImmutable $editedAt;
    private ?\DateTimeImmutable $deletedAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        CommentId $id,
        CommentableType $entityType,
        string $entityId,
        string $authorId,
        CommentContent $content,
        \DateTimeImmutable $createdAt,
        ?CommentId $parentId,
    ) {
        $this->id = $id->value();
        $this->entityType = $entityType->value();
        $this->entityId = $entityId;
        $this->authorId = $authorId;
        $this->content = $content->value();
        $this->parentId = $parentId?->value();
        $this->createdAt = $createdAt;
        $this->editedAt = null;
        $this->deletedAt = null;
    }

    public static function create(
        CommentId $id,
        CommentableType $entityType,
        string $entityId,
        string $authorId,
        CommentContent $content,
        \DateTimeImmutable $createdAt,
        ?CommentId $parentId = null,
    ): self {
        $comment = new self($id, $entityType, $entityId, $authorId, $content, $createdAt, $parentId);
        $comment->recordEvent(new CommentAdded($id, $entityType, $entityId, $authorId, $parentId));

        return $comment;
    }

    public function edit(CommentContent $content, \DateTimeImmutable $editedAt): void
    {
        $this->content = $content->value();
        $this->editedAt = $editedAt;
        $this->recordEvent(new CommentUpdated(
            $this->id(),
            $this->entityType(),
            $this->entityId,
            $this->authorId,
        ));
    }

    public function markDeleted(\DateTimeImmutable $deletedAt): void
    {
        $this->deletedAt = $deletedAt;
        $this->recordEvent(new CommentDeleted(
            $this->id(),
            $this->entityType(),
            $this->entityId,
            $this->authorId,
        ));
    }

    public function isAuthoredBy(string $userId): bool
    {
        return $this->authorId === $userId;
    }

    public function id(): CommentId
    {
        return CommentId::fromString($this->id);
    }

    public function entityType(): CommentableType
    {
        return CommentableType::fromString($this->entityType);
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function authorId(): string
    {
        return $this->authorId;
    }

    public function content(): CommentContent
    {
        return CommentContent::fromString($this->content);
    }

    public function parentId(): ?CommentId
    {
        return $this->parentId !== null ? CommentId::fromString($this->parentId) : null;
    }

    public function isReply(): bool
    {
        return $this->parentId !== null;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function editedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function deletedAt(): ?\DateTimeImmutable
    {
        return $this->deletedAt;
    }

    public function isDeleted(): bool
    {
        return $this->deletedAt !== null;
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }

    /** @return list<object> */
    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }
}
