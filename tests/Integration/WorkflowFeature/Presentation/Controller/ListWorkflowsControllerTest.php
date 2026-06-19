<?php

declare(strict_types=1);

namespace App\Tests\Integration\WorkflowFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListWorkflowsControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/workflows');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testWithoutQueryReturnsPaginatedListWithDefaults(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('getPage')
            ->with(10, 0)
            ->willReturn([$this->makeWorkflow('wf-1', 'Bug flow')]);
        $workflowService->method('countAll')->willReturn(1);
        $workflowService->expects($this->never())->method('getByIds');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchWorkflows');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $client->request('GET', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('success', $body);
        $this->assertCount(1, $body['workflow']);
        $this->assertSame('wf-1', $body['workflow'][0]['id']);
        $this->assertSame('Bug flow', $body['workflow'][0]['title']);
        $this->assertArrayNotHasKey('description', $body['workflow'][0]);
        $this->assertSame(['page' => 1, 'limit' => 10, 'pages' => 1], $body['pagination']);
        $this->assertSame(1, $body['count']);
    }

    public function testCountIsReturnedEvenWhenPageIsEmpty(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getPage')->willReturn([]);
        $workflowService->method('countAll')->willReturn(0);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['workflow']);
        $this->assertSame(0, $body['count']);
        $this->assertSame(0, $body['pagination']['pages']);
    }

    /**
     * @return array<string, array{0: int|null, 1: int}>
     */
    public static function limitProvider(): array
    {
        return [
            'default when omitted' => [null, 10],
            'explicit 10' => [10, 10],
            'explicit 20' => [20, 20],
            'explicit 50' => [50, 50],
            'invalid falls back to default' => [999, 10],
            'zero falls back to default' => [0, 10],
        ];
    }

    #[DataProvider('limitProvider')]
    public function testLimitIsValidatedAgainstAllowedValues(?int $requested, int $expected): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('getPage')
            ->with($expected, 0)
            ->willReturn([]);
        $workflowService->method('countAll')->willReturn(0);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $url = '/workflows' . ($requested !== null ? '?limit=' . $requested : '');
        $client->request('GET', $url, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame($expected, $body['pagination']['limit']);
    }

    public function testPageComputesOffsetAndPages(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('getPage')
            ->with(20, 40) // page 3, limit 20 => offset 40
            ->willReturn([]);
        $workflowService->method('countAll')->willReturn(45);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows?page=3&limit=20', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(3, $body['pagination']['page']);
        $this->assertSame(20, $body['pagination']['limit']);
        $this->assertSame(3, $body['pagination']['pages']); // ceil(45 / 20)
        $this->assertSame(45, $body['count']);
    }

    public function testShortQueryFallsBackToList(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())->method('getPage')->willReturn([]);
        $workflowService->method('countAll')->willReturn(0);
        $workflowService->expects($this->never())->method('getByIds');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchWorkflows');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $client->request('GET', '/workflows?q=a', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
    }

    public function testWithQuerySearchesPaginatedThenHydratesPreservingOrder(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->once())
            ->method('searchWorkflows')
            ->with('flow', self::USER_ID, false, 10, 0)
            ->willReturn(['ids' => ['wf-2', 'wf-1'], 'total' => 2]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->never())->method('getPage');
        $workflowService->expects($this->once())
            ->method('getByIds')
            ->with(['wf-2', 'wf-1'])
            ->willReturn([
                $this->makeWorkflow('wf-2', 'Release flow'),
                $this->makeWorkflow('wf-1', 'Bug flow'),
            ]);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows?q=flow', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['wf-2', 'wf-1'], array_column($body['workflow'], 'id'));
        $this->assertSame(2, $body['count']);
    }

    public function testSearchUsesTotalFromSearchServiceForCount(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->once())
            ->method('searchWorkflows')
            ->with('flow', self::USER_ID, true, 20, 20)
            ->willReturn(['ids' => [], 'total' => 37]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getByIds')->willReturn([]);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows?q=flow&owner=true&page=2&limit=20', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(37, $body['count']);
        $this->assertSame(2, $body['pagination']['pages']); // ceil(37 / 20)
    }

    private function makeWorkflow(string $id, string $title): WorkflowResponseInterface
    {
        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getId')->willReturn($id);
        $workflow->method('getTitle')->willReturn($title);
        $workflow->method('getCreatedBy')->willReturn(self::USER_ID);
        $workflow->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $workflow->method('getDescription')->willReturn(null);

        return $workflow;
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString(self::USER_ID),
            Email::fromString('test@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }

    private function stubUserRepository(User $user): void
    {
        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($user);
        static::getContainer()->set(UserRepositoryInterface::class, $repo);
    }

    private function generateToken(User $user): string
    {
        $jwtManager = static::getContainer()->get(JWTTokenManagerInterface::class);

        return $jwtManager->createFromPayload(new SecurityUser($user), [
            'sub' => $user->email()->value(),
        ]);
    }
}
