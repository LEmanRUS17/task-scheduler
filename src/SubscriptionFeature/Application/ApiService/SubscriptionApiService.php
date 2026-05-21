<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\ApiService;

use App\SubscriptionFeature\Application\DataMapper\SubscriptionDataMapper;
use App\SubscriptionFeature\Application\DTORequestValidator\SubscriptionValidatorInterface;
use App\SubscriptionFeature\Application\Exception\ValidationException;
use App\SubscriptionFeature\Domain\Interactor\SubscribeInteractor;
use App\SubscriptionFeature\Domain\Interactor\UnsubscribeInteractor;
use App\SubscriptionFeature\Domain\Interactor\UpdateSubscriptionInteractor;
use App\SubscriptionFeature\Domain\Repository\SubscriptionChannelRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionRepositoryInterface;
use App\SubscriptionFeature\Domain\Repository\SubscriptionTransitionRepositoryInterface;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use App\SubscriptionFeatureApi\DTORequest\SubscribeRequestInterface;
use App\SubscriptionFeatureApi\DTORequest\UpdateSubscriptionRequestInterface;
use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;
use App\SubscriptionFeatureApi\Service\SubscriptionServiceInterface;

final class SubscriptionApiService implements SubscriptionServiceInterface
{
    public function __construct(
        private readonly SubscribeInteractor $subscribeInteractor,
        private readonly UpdateSubscriptionInteractor $updateInteractor,
        private readonly UnsubscribeInteractor $unsubscribeInteractor,
        private readonly SubscriptionRepositoryInterface $subscriptions,
        private readonly SubscriptionChannelRepositoryInterface $channelRepository,
        private readonly SubscriptionTransitionRepositoryInterface $transitions,
        private readonly SubscriptionDataMapper $dataMapper,
        private readonly SubscriptionValidatorInterface $validator,
    ) {
    }

    public function subscribe(SubscribeRequestInterface $request, string $userId): SubscriptionDataResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new ValidationException($violations);
        }

        $subscription = $this->subscribeInteractor->subscribe(
            $userId,
            $this->dataMapper->requestToSubjectType($request),
            $request->getSubjectId(),
            $this->dataMapper->requestToChannelMask($request),
            $request->getTransitionIds(),
        );

        $channels = $this->channelRepository->findBySubscriptionId($subscription->id()->value());
        $transitions = $this->transitions->findBySubscriptionId($subscription->id()->value());

        return $this->dataMapper->subscriptionToResponse($subscription, $channels, $transitions);
    }

    public function update(string $subscriptionId, UpdateSubscriptionRequestInterface $request, string $userId): SubscriptionDataResponseInterface
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new ValidationException($violations);
        }

        $subscription = $this->updateInteractor->update(
            SubscriptionId::fromString($subscriptionId),
            $userId,
            $this->dataMapper->requestToChannelMask($request),
            $request->getTransitionIds(),
        );

        $channels = $this->channelRepository->findBySubscriptionId($subscription->id()->value());
        $transitions = $this->transitions->findBySubscriptionId($subscription->id()->value());

        return $this->dataMapper->subscriptionToResponse($subscription, $channels, $transitions);
    }

    public function unsubscribe(string $subscriptionId, string $userId): void
    {
        $this->unsubscribeInteractor->unsubscribe(
            SubscriptionId::fromString($subscriptionId),
            $userId,
        );
    }

    public function getById(string $subscriptionId): ?SubscriptionDataResponseInterface
    {
        $subscription = $this->subscriptions->findById(SubscriptionId::fromString($subscriptionId));

        if ($subscription === null) {
            return null;
        }

        $channels = $this->channelRepository->findBySubscriptionId($subscription->id()->value());
        $transitions = $this->transitions->findBySubscriptionId($subscription->id()->value());

        return $this->dataMapper->subscriptionToResponse($subscription, $channels, $transitions);
    }

    public function getUserSubscriptions(string $userId): array
    {
        $subscriptions = $this->subscriptions->findByUserId($userId);

        if (empty($subscriptions)) {
            return [];
        }

        $ids = array_map(fn($s) => $s->id()->value(), $subscriptions);
        $channelsBySubscription = $this->channelRepository->findBySubscriptionIds($ids);
        $transitionsBySubscription = $this->transitions->findBySubscriptionIds($ids);

        return array_map(
            fn($subscription) => $this->dataMapper->subscriptionToResponse(
                $subscription,
                $channelsBySubscription[$subscription->id()->value()] ?? [],
                $transitionsBySubscription[$subscription->id()->value()] ?? [],
            ),
            $subscriptions,
        );
    }

    public function getSubscriptionsForSubjectTransition(
        string $subjectType,
        string $subjectId,
        string $transitionId,
    ): array {
        $subscriptions = $this->subscriptions->findBySubjectAndTransition(
            SubjectType::from($subjectType),
            $subjectId,
            $transitionId,
        );

        if (empty($subscriptions)) {
            return [];
        }

        $ids = array_map(fn($s) => $s->id()->value(), $subscriptions);
        $channelsBySubscription = $this->channelRepository->findBySubscriptionIds($ids);
        $transitionsBySubscription = $this->transitions->findBySubscriptionIds($ids);

        return array_map(
            fn($subscription) => $this->dataMapper->subscriptionToResponse(
                $subscription,
                $channelsBySubscription[$subscription->id()->value()] ?? [],
                $transitionsBySubscription[$subscription->id()->value()] ?? [],
            ),
            $subscriptions,
        );
    }
}
