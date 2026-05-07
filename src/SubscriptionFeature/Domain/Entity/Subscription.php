<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Entity;

use App\SubscriptionFeature\Domain\Event\SubscriptionCreated;
use App\SubscriptionFeature\Domain\Event\SubscriptionDeleted;
use App\SubscriptionFeature\Domain\Event\SubscriptionUpdated;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;

final class Subscription
{
    private string $id;
    private string $userId;
    private string $subjectType;
    private string $subjectId;
    private \DateTimeImmutable $createdAt;

    /** @var list<object> */
    private array $domainEvents = [];

    private function __construct(
        SubscriptionId $id,
        string $userId,
        SubjectType $subjectType,
        string $subjectId,
        \DateTimeImmutable $createdAt,
    ) {
        $this->id = $id->value();
        $this->userId = $userId;
        $this->subjectType = $subjectType->value;
        $this->subjectId = $subjectId;
        $this->createdAt = $createdAt;
    }

    public static function create(
        SubscriptionId $id,
        string $userId,
        SubjectType $subjectType,
        string $subjectId,
        \DateTimeImmutable $createdAt,
    ): self {
        $subscription = new self($id, $userId, $subjectType, $subjectId, $createdAt);
        $subscription->recordEvent(new SubscriptionCreated($id, $userId, $subjectType, $subjectId));

        return $subscription;
    }

    public function updateChannels(NotificationChannelMask $channels): void
    {
        $this->recordEvent(new SubscriptionUpdated($this->id(), $channels));
    }

    public function delete(): void
    {
        $this->recordEvent(new SubscriptionDeleted($this->id(), $this->userId));
    }

    public function id(): SubscriptionId
    {
        return SubscriptionId::fromString($this->id);
    }

    public function userId(): string
    {
        return $this->userId;
    }

    public function subjectType(): SubjectType
    {
        return SubjectType::from($this->subjectType);
    }

    public function subjectId(): string
    {
        return $this->subjectId;
    }

    public function createdAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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
