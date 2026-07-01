<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetTeamListControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/team/list');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testWithoutQueryReturnsPaginatedListWithDefaults(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, 10, 0)
            ->willReturn([$this->makeTeam('team-1', 'Backend')]);
        $teamService->method('countAll')->willReturn(1);
        $teamService->expects($this->never())->method('getByIds');
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchTeams');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->expects($this->once())
            ->method('getEntityTagsByIds')
            ->with(TagServiceInterface::TYPE_TEAM, ['team-1'])
            ->willReturn(['team-1' => [
                $this->makeTag('tag-1', 'core', '#ff0000'),
                $this->makeTag('tag-2', 'priority', '#00ff00'),
            ]]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/team/list', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['teams']);
        $this->assertSame('team-1', $body['teams'][0]['id']);
        $this->assertSame('Backend', $body['teams'][0]['title']);
        $this->assertSame(
            [
                ['id' => 'tag-1', 'name' => 'core', 'color' => '#ff0000'],
                ['id' => 'tag-2', 'name' => 'priority', 'color' => '#00ff00'],
            ],
            $body['teams'][0]['tags'],
        );
        $this->assertSame(['page' => 1, 'limit' => 10, 'pages' => 1], $body['pagination']);
        $this->assertSame(1, $body['count']);
    }

    public function testCountIsReturnedEvenWhenPageIsEmpty(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getPage')->willReturn([]);
        $teamService->method('countAll')->willReturn(0);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request('GET', '/team/list', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['teams']);
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

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, $expected, 0)
            ->willReturn([]);
        $teamService->method('countAll')->willReturn(0);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $url = '/team/list' . ($requested !== null ? '?limit=' . $requested : '');
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

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->once())
            ->method('getPage')
            ->with(self::USER_ID, 20, 40) // page 3, limit 20 => offset 40
            ->willReturn([]);
        $teamService->method('countAll')->willReturn(45);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request('GET', '/team/list?page=3&limit=20', server: [
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

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->once())->method('getPage')->willReturn([]);
        $teamService->method('countAll')->willReturn(0);
        $teamService->expects($this->never())->method('getByIds');
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->never())->method('searchTeams');
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $client->request('GET', '/team/list?q=a', server: [
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
            ->method('searchTeams')
            ->with('end', self::USER_ID, [], false, 10, 0)
            ->willReturn(['ids' => ['team-2', 'team-1'], 'total' => 2]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $teamService = $this->createMock(TeamServiceInterface::class);
        $teamService->expects($this->never())->method('getPage');
        $teamService->expects($this->once())
            ->method('getByIds')
            ->with(['team-2', 'team-1'])
            ->willReturn([
                $this->makeTeam('team-2', 'Frontend'),
                $this->makeTeam('team-1', 'Backend'),
            ]);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->expects($this->once())
            ->method('getEntityTagsByIds')
            ->with(TagServiceInterface::TYPE_TEAM, ['team-2', 'team-1'])
            ->willReturn(['team-1' => [$this->makeTag('tag-9', 'legacy', '#123456')]]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/team/list?q=end', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(['team-2', 'team-1'], array_column($body['teams'], 'id'));
        $this->assertSame([], $body['teams'][0]['tags']);
        $this->assertSame(
            [['id' => 'tag-9', 'name' => 'legacy', 'color' => '#123456']],
            $body['teams'][1]['tags'],
        );
        $this->assertSame(2, $body['count']);
    }

    public function testSearchForwardsStatusAndOwnerFiltersAndUsesTotalForCount(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $search = $this->createMock(SearchServiceInterface::class);
        $search->expects($this->once())
            ->method('searchTeams')
            ->with('end', self::USER_ID, ['active', 'archived'], true, 20, 20)
            ->willReturn(['ids' => [], 'total' => 37]);
        static::getContainer()->set(SearchServiceInterface::class, $search);

        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getByIds')->willReturn([]);
        static::getContainer()->set(TeamServiceInterface::class, $teamService);

        $client->request('GET', '/team/list?q=end&status=active,archived&owner=true&page=2&limit=20', server: [
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

    private function makeTeam(string $id, string $title): TeamDataResponseInterface
    {
        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn($id);
        $team->method('getTitle')->willReturn($title);
        $team->method('getStatus')->willReturn('ACTIVE');
        $team->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01 00:00:00'));
        $team->method('getDescription')->willReturn(null);

        return $team;
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
