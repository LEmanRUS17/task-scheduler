<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DTOResponse;

use App\CommentFeatureApi\DTOResponse\CommentResponseInterface;

final class CommentResponseDTO implements CommentResponseInterface
{
    public function __construct(
        private readonly string $id,
        private readonly string $entityType,
        private readonly string $entityId,
        private readonly string $authorId,
        private readonly string $content,
        private readonly \DateTimeImmutable $createdAt,
        private readonly ?\DateTimeImmutable $editedAt = null,
        private readonly ?string $parentId = null,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getAuthorId(): string
    {
        return $this->authorId;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getEditedAt(): ?\DateTimeImmutable
    {
        return $this->editedAt;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }
}
