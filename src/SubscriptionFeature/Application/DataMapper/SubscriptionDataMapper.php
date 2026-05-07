<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DataMapper;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Entity\SubscriptionChannel;
use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeatureApi\DTORequest\SubscribeRequestInterface;
use App\SubscriptionFeatureApi\DTORequest\UpdateSubscriptionRequestInterface;
use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;

final class SubscriptionDataMapper
{
    public function requestToSubjectType(SubscribeRequestInterface $request): SubjectType
    {
        return SubjectType::from($request->getSubjectType());
    }

    public function requestToChannelMask(SubscribeRequestInterface|UpdateSubscriptionRequestInterface $request): NotificationChannelMask
    {
        return NotificationChannelMask::fromInt($request->getChannels());
    }

    /**
     * @param list<SubscriptionChannel> $channels
     * @param list<SubscriptionTransition> $transitions
     */
    public function subscriptionToResponse(
        Subscription $subscription,
        array $channels,
        array $transitions,
    ): SubscriptionDataResponseInterface {
        return new SubscriptionDataResponse(
            id: $subscription->id()->value(),
            userId: $subscription->userId(),
            subjectType: $subscription->subjectType()->value,
            subjectId: $subscription->subjectId(),
            channels: array_map(fn(SubscriptionChannel $c) => $c->channel()->value, $channels),
            transitionIds: array_map(fn($t) => $t->workflowTransitionId(), $transitions),
            createdAt: $subscription->createdAt(),
        );
    }
}
