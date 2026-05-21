<?php

declare(strict_types=1);

namespace App\SubscriptionFeatureApi\DTORequest;

interface SubscribeRequestInterface
{
    public function getSubjectType(): string;

    public function getSubjectId(): string;

    /** @return list<string> */
    public function getTransitionIds(): array;

    public function getChannels(): int;
}
