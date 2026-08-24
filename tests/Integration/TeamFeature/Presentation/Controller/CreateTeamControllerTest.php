<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeatureApi\DTORequest\TeamCreateRequestInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CreateTeamControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';
    private const WORKFLOW_ID = 'd4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f80';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/team/create');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testAttachesOwnedWorkflowToNewTeam(): void
    {
        $user = $this->makeUser('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d');

        $client = static::createClient();
        $this->stubUserRepository($user);
        $this->stubTagService([]);

        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getCreatedBy')->willReturn($user->id()->value());
        $workflow->method('isDefault')->willReturn(false);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->method('getById')->with(self::WORKFLOW_ID)->willReturn($workflow);
        $workflowService->expects($this->once())
            ->method('attachToTeam')
            ->with(self::WORKFLOW_ID, self::TEAM_ID, $user->id()->value());
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn(self::TEAM_ID);
        $team->method('getTitle')->willReturn('Backend');
        $team->method('getStatus')->willReturn('ACTIVE');
        $team->method('getDescription')->willReturn(null);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->once())
            ->method('create')
            ->with(
                $this->callback(
                    fn(TeamCreateRequestInterface $request) => $request->getWorkflowId() === self::WORKFLOW_ID,
                ),
                $user->id()->value(),
            )
            ->willReturn($team);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request(
            'POST',
            '/team/create',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Backend', 'workflowId' => self::WORKFLOW_ID]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::WORKFLOW_ID, $body['workflowId']);
    }

    public function testRejectsWorkflowNotOwnedByCreator(): void
    {
        $user = $this->makeUser('b2c3d4e5-f6a7-4b8c-9d0e-1f2a3b4c5d6e');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getCreatedBy')->willReturn('someone-else');
        $workflow->method('isDefault')->willReturn(false);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->method('getById')->with(self::WORKFLOW_ID)->willReturn($workflow);
        $workflowService->expects($this->never())->method('attachToTeam');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->never())->method('create');
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request(
            'POST',
            '/team/create',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Backend', 'workflowId' => self::WORKFLOW_ID]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('workflowId', $body['errors']);
    }

    public function testRejectsUnknownWorkflowId(): void
    {
        $user = $this->makeUser('c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7a');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->method('getById')->with(self::WORKFLOW_ID)->willReturn(null);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->never())->method('create');
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request(
            'POST',
            '/team/create',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Backend', 'workflowId' => self::WORKFLOW_ID]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testRejectsDefaultWorkflow(): void
    {
        $user = $this->makeUser('d4e5f6a7-b8c9-4d0e-1f2a-3b4c5d6e7f81');

        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getCreatedBy')->willReturn($user->id()->value());
        $workflow->method('isDefault')->willReturn(true);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->method('getById')->with(self::WORKFLOW_ID)->willReturn($workflow);
        $workflowService->expects($this->never())->method('attachToTeam');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->never())->method('create');
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request(
            'POST',
            '/team/create',
            server: [
                'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
                'CONTENT_TYPE' => 'application/json',
            ],
            content: json_encode(['title' => 'Backend', 'workflowId' => self::WORKFLOW_ID]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function stubTagService(array $tagsByTeam): void
    {
        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('getEntityTagsByIds')->willReturn($tagsByTeam);
        static::getContainer()->set(TagServiceInterface::class, $tagService);
    }

    private function makeUser(string $userId): User
    {
        return User::register(
            UserId::fromString($userId),
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
