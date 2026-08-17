<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskFeature\Presentation\Controller;

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

final class GetTaskCreateFormControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/task/create');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsEmptyFormWithOwnDefaultWorkflowPreselected(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('getDefaultForUser')
            ->with(self::USER_ID)
            ->willReturn($this->makeWorkflow('wf-default'));
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/task/create', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame(
            [
                'title' => '',
                'workflow' => 'wf-default',
                'priority' => null,
                'teamId' => null,
                'assigneeIds' => [],
                'tagIds' => [],
                'scheduledStart' => null,
                'scheduledEnd' => null,
                'estimatedTime' => null,
                'description' => null,
            ],
            $body,
        );
    }

    public function testWorkflowIsNullWhenUserHasNoDefaultWorkflow(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getDefaultForUser')->willReturn(null);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/task/create', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertNull($body['workflow']);
    }

    private function makeWorkflow(string $id): WorkflowResponseInterface
    {
        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getId')->willReturn($id);

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
