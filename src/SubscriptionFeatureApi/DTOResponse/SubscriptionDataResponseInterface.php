<?php

declare(strict_types=1);

namespace App\SubscriptionFeatureApi\DTOResponse;

interface SubscriptionDataResponseInterface
{
    public function getId(): string;

    public function getUserId(): string;

    public function getSubjectType(): string;

    public function getSubjectId(): string;

    /** @return list<string> */
    public function getChannels(): array;

    /** @return list<string> */
    public function getTransitionIds(): array;

    public function getCreatedAt(): \DateTimeImmutable;
}
