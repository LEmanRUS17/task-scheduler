<?php

declare(strict_types=1);

namespace App\CommentFeatureApi\DTOResponse;

interface CommentResponseInterface
{
    public function getId(): string;

    public function getEntityType(): string;

    public function getEntityId(): string;

    public function getAuthorId(): string;

    public function getContent(): string;

    public function getParentId(): ?string;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getEditedAt(): ?\DateTimeImmutable;
}
