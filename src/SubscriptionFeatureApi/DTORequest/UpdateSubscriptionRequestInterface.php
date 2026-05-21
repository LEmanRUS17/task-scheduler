<?php

declare(strict_types=1);

namespace App\SubscriptionFeatureApi\DTORequest;

interface UpdateSubscriptionRequestInterface
{
    /** @return list<string> */
    public function getTransitionIds(): array;

    public function getChannels(): int;
}
