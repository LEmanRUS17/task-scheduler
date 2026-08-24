<?php

declare(strict_types=1);

namespace App\Tests\Integration\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class DetachWorkflowFromTeamControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('DELETE', '/workflows/wf-1/teams/team-1');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsForbiddenWhenCallerIsNotATeamMember(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $membership = $this->createMock(TeamMembershipInterface::class);
        $membership->expects($this->once())
            ->method('isMember')
            ->with('team-1', self::USER_ID)
            ->willReturn(false);
        static::getContainer()->set(TeamMembershipInterface::class, $membership);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->never())->method('detachFromTeam');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('DELETE', '/workflows/wf-1/teams/team-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testDetachesWorkflowWhenCallerIsATeamMember(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);
        static::getContainer()->set(TeamMembershipInterface::class, $membership);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('detachFromTeam')
            ->with('wf-1', 'team-1', self::USER_ID);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('DELETE', '/workflows/wf-1/teams/team-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
    }

    public function testReturnsConflictWhenServiceRejectsDetachment(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);
        static::getContainer()->set(TeamMembershipInterface::class, $membership);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('detachFromTeam')->willThrowException(
            new \DomainException('Workflow is not attached to this team'),
        );
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('DELETE', '/workflows/wf-1/teams/team-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
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
