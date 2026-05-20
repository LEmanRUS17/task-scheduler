<?php

declare(strict_types=1);

namespace App\Tests\Unit\AnalyticsFeature\Infrastructure\ClickHouse;

use App\AnalyticsFeature\Infrastructure\ClickHouse\ClickHouseAnalyticsQuery;
use App\AnalyticsFeature\Infrastructure\ClickHouse\ClickHouseClient;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class ClickHouseAnalyticsQueryTest extends TestCase
{
    private function buildQuery(MockResponse ...$responses): ClickHouseAnalyticsQuery
    {
        $httpClient = new MockHttpClient($responses);
        $client = new ClickHouseClient($httpClient, 'http://clickhouse:8123', 'user', 'pass');

        return new ClickHouseAnalyticsQuery($client);
    }

    private function from(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2025-01-01 00:00:00');
    }

    private function to(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('2025-12-31 23:59:59');
    }

    public function testAvgTimePerStatusReturnsMappedRows(): void
    {
        $body = implode("\n", [
            json_encode(['status' => 'todo',        'avg_seconds' => '1800.5']),
            json_encode(['status' => 'in_progress', 'avg_seconds' => '7200.0']),
        ]);

        $result = $this->buildQuery(new MockResponse($body))->avgTimePerStatus($this->from(), $this->to());

        $this->assertCount(2, $result);
        $this->assertSame('todo', $result[0]['status']);
        $this->assertSame(1800.5, $result[0]['avg_seconds']);
        $this->assertIsFloat($result[0]['avg_seconds']);
        $this->assertIsFloat($result[1]['avg_seconds']);
    }

    public function testAvgTimePerStatusReturnsEmptyArrayWhenNoRows(): void
    {
        $result = $this->buildQuery(new MockResponse(''))->avgTimePerStatus($this->from(), $this->to());

        $this->assertSame([], $result);
    }

    public function testStatusTransitionsPerDayReturnsMappedRows(): void
    {
        $body = implode("\n", [
            json_encode(['day' => '2025-03-01', 'status' => 'done',        'count' => '5']),
            json_encode(['day' => '2025-03-01', 'status' => 'in_progress', 'count' => '12']),
        ]);

        $result = $this->buildQuery(new MockResponse($body))->statusTransitionsPerDay($this->from(), $this->to());

        $this->assertCount(2, $result);
        $this->assertSame('2025-03-01', $result[0]['day']);
        $this->assertSame('done', $result[0]['status']);
        $this->assertSame(5, $result[0]['count']);
        $this->assertIsInt($result[0]['count']);
        $this->assertIsInt($result[1]['count']);
    }

    public function testStatusTransitionsPerDayReturnsEmptyArrayWhenNoRows(): void
    {
        $result = $this->buildQuery(new MockResponse(''))->statusTransitionsPerDay($this->from(), $this->to());

        $this->assertSame([], $result);
    }

    public function testCrudActionsCountReturnsMappedRows(): void
    {
        $body = implode("\n", [
            json_encode(['action' => 'create', 'count' => '10']),
            json_encode(['action' => 'delete', 'count' => '3']),
        ]);

        $result = $this->buildQuery(new MockResponse($body))->crudActionsCount($this->from(), $this->to());

        $this->assertCount(2, $result);
        $this->assertSame('create', $result[0]['action']);
        $this->assertSame(10, $result[0]['count']);
        $this->assertIsInt($result[0]['count']);
        $this->assertIsInt($result[1]['count']);
    }

    public function testCrudActionsCountReturnsEmptyArrayWhenNoRows(): void
    {
        $result = $this->buildQuery(new MockResponse(''))->crudActionsCount($this->from(), $this->to());

        $this->assertSame([], $result);
    }
}
