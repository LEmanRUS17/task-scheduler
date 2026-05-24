<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Entity;

use App\SubscriptionFeature\Domain\Entity\SubscriptionTransition;
use PHPUnit\Framework\TestCase;

final class SubscriptionTransitionTest extends TestCase
{
    public function testCreateStoresFields(): void
    {
        $transition = SubscriptionTransition::create('sub-uuid', 'transition-uuid');

        $this->assertSame('sub-uuid', $transition->subscriptionId());
        $this->assertSame('transition-uuid', $transition->workflowTransitionId());
    }
}
