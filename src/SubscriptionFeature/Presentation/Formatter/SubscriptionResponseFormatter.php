<?php

declare(strict_types=1);

namespace App\SubscriptionFeature\Presentation\Formatter;

use App\SubscriptionFeatureApi\DTOResponse\SubscriptionDataResponseInterface;

final class SubscriptionResponseFormatter
{
    /** @return array<string, mixed> */
    public static function format(SubscriptionDataResponseInterface $s): array
    {
        return [
            'id' => $s->getId(),
            'userId' => $s->getUserId(),
            'subjectType' => $s->getSubjectType(),
            'subjectId' => $s->getSubjectId(),
            'channels' => $s->getChannels(),
            'transitionIds' => $s->getTransitionIds(),
            'createdAt' => $s->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ];
    }
}
