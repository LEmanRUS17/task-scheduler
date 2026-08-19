<?php

declare(strict_types=1);

namespace App\Tests\Integration\WorkflowFeature\Presentation\Controller;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Exception\WorkflowAccessDeniedException;
use App\WorkflowFeatureApi\DTOResponse\WorkflowResponseInterface;
use App\WorkflowFeatureApi\Service\WorkflowServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UpdateWorkflowControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('PUT', '/workflows/wf-1', content: json_encode(['title' => 'Renamed']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsForbiddenWhenCallerIsNotTheWorkflowOwner(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('update')->willThrowException(
            WorkflowAccessDeniedException::notOwner('wf-1'),
        );
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('PUT', '/workflows/wf-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode(['title' => 'Renamed']));

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testUpdatesWorkflowWhenCallerIsTheOwner(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('update')
            ->with('wf-1', $this->anything(), self::USER_ID)
            ->willReturn($this->makeWorkflow());
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('PUT', '/workflows/wf-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode(['title' => 'Renamed']));

        $this->assertResponseIsSuccessful();
    }

    private function makeWorkflow(): WorkflowResponseInterface
    {
        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getId')->willReturn('wf-1');
        $workflow->method('getTitle')->willReturn('Renamed');
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
