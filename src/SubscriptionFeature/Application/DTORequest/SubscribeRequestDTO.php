<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Application\DTORequest;

use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeatureApi\DTORequest\SubscribeRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class SubscribeRequestDTO implements SubscribeRequestInterface
{
    /**
     * @param list<string> $transitionIds
     */
    public function __construct(
        #[Assert\NotBlank(message: 'Subject type is required')]
        #[Assert\Choice(choices: ['task'], message: 'Invalid subject type')]
        private readonly string $subjectType,
        #[Assert\NotBlank(message: 'Subject ID is required')]
        #[Assert\Uuid(message: 'Subject ID must be a valid UUID')]
        private readonly string $subjectId,
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

    public function getSubjectType(): string
    {
        return $this->subjectType;
    }

    public function getSubjectId(): string
    {
        return $this->subjectId;
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
