<?php

declare(strict_types=1);

namespace App\Tests\Integration\WorkflowFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use App\WorkflowFeature\Domain\Port\TeamMembershipInterface;
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
            ->with(10, 0, self::USER_ID, null)
            ->willReturn([$this->makeWorkflow('wf-1', 'Bug flow', isDefault: true)]);
        $workflowService->method('countAll')->willReturn(1);
        $workflowService->expects($this->never())->method('getByIds');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchWorkflows');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->expects($this->once())
            ->method('getEntityTagsByIds')
            ->with(TagServiceInterface::TYPE_WORKFLOW, ['wf-1'])
            ->willReturn(['wf-1' => [
                $this->makeTag('tag-1', 'agile', '#ff0000'),
                $this->makeTag('tag-2', 'kanban', '#00ff00'),
            ]]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/workflows', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayNotHasKey('success', $body);
        $this->assertCount(1, $body['workflow']);
        $this->assertSame('wf-1', $body['workflow'][0]['id']);
        $this->assertSame('Bug flow', $body['workflow'][0]['title']);
        $this->assertTrue($body['workflow'][0]['isDefault']);
        $this->assertArrayNotHasKey('description', $body['workflow'][0]);
        $this->assertSame(
            [
                ['id' => 'tag-1', 'name' => 'agile', 'color' => '#ff0000'],
                ['id' => 'tag-2', 'name' => 'kanban', 'color' => '#00ff00'],
            ],
            $body['workflow'][0]['tags'],
        );
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
            ->with($expected, 0, self::USER_ID, null)
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
            ->with(20, 40, self::USER_ID, null) // page 3, limit 20 => offset 40
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

    public function testTeamIdIsRejectedWhenCallerIsNotAMember(): void
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
        $workflowService->expects($this->never())->method('getPage');
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows?teamId=team-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    public function testTeamIdIsPassedThroughAndTeamTitleIsReturnedWhenCallerIsAMember(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(true);
        static::getContainer()->set(TeamMembershipInterface::class, $membership);

        $workflow = $this->makeWorkflow('wf-1', 'Shared flow', teamTitle: 'Engineering');

        $workflowService = $this->createMock(WorkflowServiceInterface::class);
        $workflowService->expects($this->once())
            ->method('getPage')
            ->with(10, 0, self::USER_ID, 'team-1')
            ->willReturn([$workflow]);
        $workflowService->expects($this->once())
            ->method('countAll')
            ->with(self::USER_ID, 'team-1')
            ->willReturn(1);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $tagService = $this->createStub(TagServiceInterface::class);
        $tagService->method('getEntityTagsByIds')->willReturn([]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/workflows?teamId=team-1', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Engineering', $body['workflow'][0]['teamTitle']);
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
            ->with('flow', self::USER_ID, 10, 0)
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

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->expects($this->once())
            ->method('getEntityTagsByIds')
            ->with(TagServiceInterface::TYPE_WORKFLOW, ['wf-2', 'wf-1'])
            ->willReturn(['wf-1' => [$this->makeTag('tag-9', 'scrum', '#123456')]]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/workflows?q=flow', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['wf-2', 'wf-1'], array_column($body['workflow'], 'id'));
        $this->assertSame([], $body['workflow'][0]['tags']);
        $this->assertSame(
            [['id' => 'tag-9', 'name' => 'scrum', 'color' => '#123456']],
            $body['workflow'][1]['tags'],
        );
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
            ->with('flow', self::USER_ID, 20, 20)
            ->willReturn(['ids' => [], 'total' => 37]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $workflowService = $this->createStub(WorkflowServiceInterface::class);
        $workflowService->method('getByIds')->willReturn([]);
        static::getContainer()->set(WorkflowServiceInterface::class, $workflowService);

        $client->request('GET', '/workflows?q=flow&page=2&limit=20', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(37, $body['count']);
        $this->assertSame(2, $body['pagination']['pages']); // ceil(37 / 20)
    }

    private function makeTag(string $id, string $name, string $color): TagResponseInterface
    {
        $tag = $this->createStub(TagResponseInterface::class);
        $tag->method('getId')->willReturn($id);
        $tag->method('getName')->willReturn($name);
        $tag->method('getColor')->willReturn($color);

        return $tag;
    }

    private function makeWorkflow(
        string $id,
        string $title,
        bool $isDefault = false,
        ?string $teamTitle = null,
    ): WorkflowResponseInterface {
        $workflow = $this->createStub(WorkflowResponseInterface::class);
        $workflow->method('getId')->willReturn($id);
        $workflow->method('getTitle')->willReturn($title);
        $workflow->method('getCreatedBy')->willReturn(self::USER_ID);
        $workflow->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2024-01-01 00:00:00'));
        $workflow->method('getDescription')->willReturn(null);
        $workflow->method('isDefault')->willReturn($isDefault);
        $workflow->method('getTeamTitle')->willReturn($teamTitle);

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
