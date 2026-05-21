<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Domain\Entity;

final class SubscriptionTransition
{
    private string $subscriptionId;
    private string $workflowTransitionId;

    private function __construct(string $subscriptionId, string $workflowTransitionId)
    {
        $this->subscriptionId = $subscriptionId;
        $this->workflowTransitionId = $workflowTransitionId;
    }

    public static function create(string $subscriptionId, string $workflowTransitionId): self
    {
        return new self($subscriptionId, $workflowTransitionId);
    }

    public function subscriptionId(): string
    {
        return $this->subscriptionId;
    }

    public function workflowTransitionId(): string
    {
        return $this->workflowTransitionId;
    }
}
