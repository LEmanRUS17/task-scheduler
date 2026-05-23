<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\Entity;

use App\SubscriptionFeature\Domain\Entity\Subscription;
use App\SubscriptionFeature\Domain\Event\SubscriptionCreated;
use App\SubscriptionFeature\Domain\Event\SubscriptionDeleted;
use App\SubscriptionFeature\Domain\Event\SubscriptionUpdated;
use App\SubscriptionFeature\Domain\ValueObject\NotificationChannelMask;
use App\SubscriptionFeature\Domain\ValueObject\SubjectType;
use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use PHPUnit\Framework\TestCase;

final class SubscriptionTest extends TestCase
{
    private SubscriptionId $id;
    private \DateTimeImmutable $createdAt;

    protected function setUp(): void
    {
        $this->id = SubscriptionId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $this->createdAt = new \DateTimeImmutable('2024-01-01 12:00:00');
    }

    private function makeSubscription(): Subscription
    {
        return Subscription::create(
            $this->id,
            'user-1',
            SubjectType::TASK,
            'task-uuid',
            $this->createdAt,
        );
    }

    public function testCreateStoresFields(): void
    {
        $subscription = $this->makeSubscription();

        $this->assertSame($this->id->value(), $subscription->id()->value());
        $this->assertSame('user-1', $subscription->userId());
        $this->assertSame(SubjectType::TASK, $subscription->subjectType());
        $this->assertSame('task-uuid', $subscription->subjectId());
        $this->assertSame($this->createdAt, $subscription->createdAt());
    }

    public function testCreateRecordsSubscriptionCreatedEvent(): void
    {
        $subscription = $this->makeSubscription();
        $events = $subscription->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SubscriptionCreated::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame('user-1', $events[0]->userId);
        $this->assertSame(SubjectType::TASK, $events[0]->subjectType);
        $this->assertSame('task-uuid', $events[0]->subjectId);
    }

    public function testPullDomainEventsClearsQueue(): void
    {
        $subscription = $this->makeSubscription();
        $subscription->pullDomainEvents();

        $this->assertEmpty($subscription->pullDomainEvents());
    }

    public function testUpdateChannelsRecordsSubscriptionUpdatedEvent(): void
    {
        $subscription = $this->makeSubscription();
        $subscription->pullDomainEvents();

        $mask = NotificationChannelMask::fromInt(1);
        $subscription->updateChannels($mask);

        $events = $subscription->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SubscriptionUpdated::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame(1, $events[0]->channels->value());
    }

    public function testDeleteRecordsSubscriptionDeletedEvent(): void
    {
        $subscription = $this->makeSubscription();
        $subscription->pullDomainEvents();

        $subscription->delete();

        $events = $subscription->pullDomainEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(SubscriptionDeleted::class, $events[0]);
        $this->assertSame($this->id->value(), $events[0]->id->value());
        $this->assertSame('user-1', $events[0]->userId);
    }
}
