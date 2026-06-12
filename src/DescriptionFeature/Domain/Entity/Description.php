<?php

declare(strict_types=1);

namespace App\DescriptionFeature\Domain\Entity;

final class Description
{
    private string $id;
    private string $entityClass;
    private string $entityId;
    private string $content;
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        string $id,
        string $entityClass,
        string $entityId,
        string $content,
        \DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->entityClass = $entityClass;
        $this->entityId = $entityId;
        $this->content = $content;
        $this->updatedAt = $updatedAt;
    }

    public static function create(
        string $id,
        string $entityClass,
        string $entityId,
        string $content,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self($id, $entityClass, $entityId, $content, $updatedAt);
    }

    public function update(string $content, \DateTimeImmutable $updatedAt): void
    {
        $this->content = $content;
        $this->updatedAt = $updatedAt;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function entityClass(): string
    {
        return $this->entityClass;
    }

    public function entityId(): string
    {
        return $this->entityId;
    }

    public function content(): string
    {
        return $this->content;
    }

    public function updatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
