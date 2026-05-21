<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DataMapper;

use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;

final class SubscriptionDataResponse implements SubscriptionDataResponseInterface
{
    /**
     * @param list<string> $channels
     * @param list<string> $transitionIds
     */
    public function __construct(
        private readonly string $id,
        private readonly string $userId,
        private readonly string $subjectType,
        private readonly string $subjectId,
        private readonly array $channels,
        private readonly array $transitionIds,
        private readonly \DateTimeImmutable $createdAt,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function getSubjectId(): string
    {
        return $this->subjectId;
    }

    public function getChannels(): array
    {
        return $this->channels;
    }

    public function getTransitionIds(): array
    {
        return $this->transitionIds;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
