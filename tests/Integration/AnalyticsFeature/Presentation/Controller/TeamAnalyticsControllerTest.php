<?php

declare(strict_types=1);

namespace App\Tests\Integration\AnalyticsFeature\Presentation\Controller;

use App\AnalyticsFeatureApi\Contract\AnalyticsServiceInterface;
use App\AnalyticsFeatureApi\DTOResponse\TeamAnalyticsResponseInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TeamAnalyticsControllerTest extends WebTestCase
{
    public function testReturnsBadRequestWhenFromIsMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics?to=2025-12-31');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsBadRequestWhenToIsMissing(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics?from=2025-01-01');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsBadRequestWhenFromHasInvalidFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics?from=01-01-2025&to=2025-12-31');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsBadRequestWhenToHasInvalidFormat(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics?from=2025-01-01&to=31-12-2025');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsBadRequestWhenFromIsAfterTo(): void
    {
        $client = static::createClient();
        $client->request('GET', '/analytics?from=2025-12-31&to=2025-01-01');
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsOkWithExpectedStructure(): void
    {
        $client = static::createClient();

        $response = $this->createStub(TeamAnalyticsResponseInterface::class);
        $response->method('getAvgTimePerStatus')->willReturn([
            ['status' => 'todo', 'avg_seconds' => 3600.0],
        ]);
        $response->method('getCompletedCount')->willReturn(0);
        $response->method('getThroughputPerDay')->willReturn([]);
        $response->method('getCrudActionsCount')->willReturn([
            ['action' => 'create', 'count' => 5],
        ]);
        $response->method('getStatusTransitionsPerDay')->willReturn([
            ['day' => '2025-03-01', 'status' => 'done', 'count' => 3],
        ]);

        $service = $this->createMock(AnalyticsServiceInterface::class);
        $service->expects($this->once())->method('getAnalytics')->willReturn($response);
        static::getContainer()->set(AnalyticsServiceInterface::class, $service);

        $client->request('GET', '/analytics?from=2025-01-01&to=2025-12-31');

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('avg_time_per_status', $body);
        $this->assertArrayHasKey('crud_actions_count', $body);
        $this->assertArrayHasKey('status_transitions_per_day', $body);
        $this->assertArrayNotHasKey('completed_count', $body);

        $this->assertSame('todo', $body['avg_time_per_status'][0]['status']);
        $this->assertSame('done', $body['status_transitions_per_day'][0]['status']);
        $this->assertSame(3, $body['status_transitions_per_day'][0]['count']);
    }
}
