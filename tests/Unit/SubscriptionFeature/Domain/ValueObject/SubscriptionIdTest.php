<?php

declare(strict_types=1);

namespace App\Tests\Unit\SubscriptionFeature\Domain\ValueObject;

use App\SubscriptionFeature\Domain\ValueObject\SubscriptionId;
use PHPUnit\Framework\TestCase;

final class SubscriptionIdTest extends TestCase
{
    public function testGenerateReturnsValidUuidV4(): void
    {
        $id = SubscriptionId::generate();

        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $id->value(),
        );
    }

    public function testGenerateReturnsUniqueValues(): void
    {
        $this->assertNotSame(SubscriptionId::generate()->value(), SubscriptionId::generate()->value());
    }

    public function testFromStringAcceptsValidUuid(): void
    {
        $uuid = '550e8400-e29b-4d4d-a716-446655440000';
        $id = SubscriptionId::fromString($uuid);

        $this->assertSame($uuid, $id->value());
    }

    public function testFromStringThrowsOnInvalidUuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        SubscriptionId::fromString('not-a-uuid');
    }

    public function testFromStringThrowsOnNonV4Uuid(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        // version 1 UUID
        SubscriptionId::fromString('550e8400-e29b-11d4-a716-446655440000');
    }

    public function testEqualsReturnsTrueForSameValue(): void
    {
        $uuid = '550e8400-e29b-4d4d-a716-446655440000';

        $this->assertTrue(SubscriptionId::fromString($uuid)->equals(SubscriptionId::fromString($uuid)));
    }

    public function testEqualsReturnsFalseForDifferentValues(): void
    {
        $a = SubscriptionId::fromString('550e8400-e29b-4d4d-a716-446655440000');
        $b = SubscriptionId::fromString('550e8400-e29b-4d4d-a716-446655440001');

        $this->assertFalse($a->equals($b));
    }
}
