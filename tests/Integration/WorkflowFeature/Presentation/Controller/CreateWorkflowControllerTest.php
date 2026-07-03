<?php

declare(strict_types=1);

namespace App\Tests\Integration\WorkflowFeature\Presentation\Controller;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Entity\WorkflowStatus;
use App\WorkflowFeature\Domain\Entity\WorkflowTransition;
use App\WorkflowFeature\Domain\Repository\WorkflowRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowStatusRepositoryInterface;
use App\WorkflowFeature\Domain\Repository\WorkflowTransitionRepositoryInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class CreateWorkflowControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/workflows', content: json_encode(['title' => 'Bug flow']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testCreateWithBranchingGraphReturnsAllStatusesAndTransitions(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);
        $this->stubWorkflowRepositories();
        $this->stubTagAndDescriptionServices();

        $client->request('POST', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'title' => 'Bug workflow',
            'statuses' => [
                ['label' => 'open', 'isInitial' => true],
                ['label' => 'in_progress'],
                ['label' => 'review'],
                ['label' => 'done', 'isFinal' => true],
                ['label' => 'rejected', 'isFinal' => true],
            ],
            'transitions' => [
                ['name' => 'start_progress', 'from' => 'open', 'to' => 'in_progress'],
                ['name' => 'submit_review', 'from' => 'in_progress', 'to' => 'review'],
                ['name' => 'approve', 'from' => 'review', 'to' => 'done'],
                ['name' => 'reject', 'from' => 'review', 'to' => 'rejected'],
                ['name' => 'rework', 'from' => 'review', 'to' => 'in_progress'],
            ],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = json_decode($client->getResponse()->getContent(), true);

        $this->assertSame('Bug workflow', $body['title']);
        $this->assertCount(5, $body['statuses']);
        $this->assertCount(5, $body['transitions']);

        $statusesByLabel = [];
        foreach ($body['statuses'] as $status) {
            $statusesByLabel[$status['label']] = $status;
        }

        $this->assertTrue($statusesByLabel['open']['isInitial']);
        $this->assertFalse($statusesByLabel['open']['isFinal']);
        $this->assertTrue($statusesByLabel['done']['isFinal']);
        $this->assertTrue($statusesByLabel['rejected']['isFinal']);

        $rework = current(array_filter($body['transitions'], static fn($t) => $t['name'] === 'rework'));
        $this->assertSame($statusesByLabel['review']['id'], $rework['from']);
        $this->assertSame($statusesByLabel['in_progress']['id'], $rework['to']);
    }

    public function testCreateRejectsGraphWhereFinalStatusIsUnreachable(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);
        $this->stubWorkflowRepositories();
        $this->stubTagAndDescriptionServices();

        $client->request('POST', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'title' => 'Broken workflow',
            'statuses' => [
                ['label' => 'open', 'isInitial' => true],
                ['label' => 'orphan'],
                ['label' => 'done', 'isFinal' => true],
            ],
            'transitions' => [
                ['name' => 'finish', 'from' => 'orphan', 'to' => 'done'],
            ],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CONFLICT);
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertStringContainsString('reachable', $body['message']);
    }

    public function testCreateRejectsFewerThanTwoStatuses(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);
        $this->stubWorkflowRepositories();
        $this->stubTagAndDescriptionServices();

        $client->request('POST', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'title' => 'Too small workflow',
            'statuses' => [
                ['label' => 'open', 'isInitial' => true],
            ],
            'transitions' => [],
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function stubWorkflowRepositories(): void
    {
        $workflows = $this->createStub(WorkflowRepositoryInterface::class);
        static::getContainer()->set(WorkflowRepositoryInterface::class, $workflows);

        /** @var WorkflowStatus[] $savedStatuses */
        $savedStatuses = [];
        $statuses = $this->createMock(WorkflowStatusRepositoryInterface::class);
        $statuses->method('save')->willReturnCallback(function (WorkflowStatus $status) use (&$savedStatuses): void {
            $savedStatuses[] = $status;
        });
        $statuses->method('findByWorkflowId')->willReturnCallback(
            function () use (&$savedStatuses): array {
                return $savedStatuses;
            },
        );
        static::getContainer()->set(WorkflowStatusRepositoryInterface::class, $statuses);

        /** @var WorkflowTransition[] $savedTransitions */
        $savedTransitions = [];
        $transitions = $this->createMock(WorkflowTransitionRepositoryInterface::class);
        $transitions->method('save')->willReturnCallback(
            function (WorkflowTransition $transition) use (&$savedTransitions): void {
                $savedTransitions[] = $transition;
            },
        );
        $transitions->method('findByWorkflowId')->willReturnCallback(
            function () use (&$savedTransitions): array {
                return $savedTransitions;
            },
        );
        static::getContainer()->set(WorkflowTransitionRepositoryInterface::class, $transitions);
    }

    private function stubTagAndDescriptionServices(): void
    {
        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('filterExistingTagIds')->willReturnArgument(0);
        $tagService->method('getEntityTagsByIds')->willReturn([]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $descriptions = $this->createStub(DescriptionServiceInterface::class);
        static::getContainer()->set(DescriptionServiceInterface::class, $descriptions);
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

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString(self::USER_ID),
            Email::fromString('test@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }
}
