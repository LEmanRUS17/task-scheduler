<?php

declare(strict_types=1);

namespace App\SubscriptionFeatureApi\Service;

use App\SubscriptionFeatureApi\DTORequest\SubscribeRequestInterface;
use App\SubscriptionFeatureApi\DTORequest\UpdateSubscriptionRequestInterface;
use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;

interface SubscriptionServiceInterface
{
    public function subscribe(SubscribeRequestInterface $request, string $userId): SubscriptionDataResponseInterface;

    public function update(string $subscriptionId, UpdateSubscriptionRequestInterface $request, string $userId): SubscriptionDataResponseInterface;

    public function unsubscribe(string $subscriptionId, string $userId): void;

    public function getById(string $subscriptionId): ?SubscriptionDataResponseInterface;

    /** @return list<SubscriptionDataResponseInterface> */
    public function getUserSubscriptions(string $userId): array;

    /**
     * @return list<SubscriptionDataResponseInterface>
     */
    public function getSubscriptionsForSubjectTransition(
        string $subjectType,
        string $subjectId,
        string $transitionId,
    ): array;
}
