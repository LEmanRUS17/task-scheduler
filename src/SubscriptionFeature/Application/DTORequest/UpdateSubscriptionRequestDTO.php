<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DTORequest;

use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeatureApi\DTORequest\UpdateSubscriptionRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateSubscriptionRequestDTO implements UpdateSubscriptionRequestInterface
{
    /**
     * @param list<string> $transitionIds
     */
    public function __construct(
        #[Assert\NotNull(message: 'Transition IDs is required')]
        #[Assert\Count(min: 1, minMessage: 'At least one transition must be specified')]
        #[Assert\All([new Assert\Uuid(message: 'Each transition ID must be a valid UUID')])]
        private readonly array $transitionIds,
        #[Assert\NotNull(message: 'Channels is required')]
        #[Assert\Range(
            min: 1,
            max: NotificationChannelMask::MAX,
            notInRangeMessage: 'Channels mask must be between {{ min }} and {{ max }}',
        )]
        private readonly int $channels,
    ) {
    }

    public function getTransitionIds(): array
    {
        return $this->transitionIds;
    }

    public function getChannels(): int
    {
        return $this->channels;
    }
}
