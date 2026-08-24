<?php

declare(strict_types=1);

namespace App\Tests\Integration\TeamFeature\Presentation\Controller;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class GetTeamControllerTest extends WebTestCase
{
    private const TEAM_ID = 'c3d4e5f6-a7b8-4c9d-0e1f-2a3b4c5d6e7f';
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/team/' . self::TEAM_ID);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsNotFoundWhenTeamDoesNotExist(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('getByIdForUser')
            ->with(self::TEAM_ID, self::USER_ID)
            ->willThrowException(new \DomainException('Team ' . self::TEAM_ID . ' not found'));
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $client->request('GET', '/team/' . self::TEAM_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    public function testReturnsTeamWithTagsAndMembers(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn(self::TEAM_ID);
        $team->method('getTitle')->willReturn('Backend');
        $team->method('getStatus')->willReturn('ACTIVE');
        $team->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-01-01T00:00:00+00:00'));
        $team->method('getDescription')->willReturn(null);

        $profile = $this->createStub(ProfileDataResponseInterface::class);
        $profile->method('getUserId')->willReturn('member-1');
        $profile->method('getUsername')->willReturn('member1');
        $profile->method('getFirstname')->willReturn('First');
        $profile->method('getLastname')->willReturn('Last');
        $profile->method('getMidlname')->willReturn(null);
        $profile->method('getStatus')->willReturn('active');
        $profile->method('getAvatar')->willReturn(null);

        $member = $this->createStub(TeamMemberDataResponseInterface::class);
        $member->method('getUserId')->willReturn('member-1');
        $member->method('getRole')->willReturn('member');
        $member->method('getJoinedAt')->willReturn(new \DateTimeImmutable('2026-01-02T00:00:00+00:00'));
        $member->method('getProfile')->willReturn($profile);

        $service = $this->createMock(TeamServiceInterface::class);
        $service->expects($this->once())
            ->method('getByIdForUser')
            ->with(self::TEAM_ID, self::USER_ID)
            ->willReturn($team);
        $service->expects($this->once())
            ->method('getMembers')
            ->with(self::TEAM_ID)
            ->willReturn([$member]);
        $service->expects($this->once())
            ->method('getOwners')
            ->with(self::TEAM_ID)
            ->willReturn([self::USER_ID]);
        static::getContainer()->set(TeamServiceInterface::class, $service);

        $tagService = $this->createMock(TagServiceInterface::class);
        $tagService->expects($this->once())
            ->method('getEntityTagsByIds')
            ->with(TagServiceInterface::TYPE_TEAM, [self::TEAM_ID])
            ->willReturn([]);
        static::getContainer()->set(TagServiceInterface::class, $tagService);

        $client->request('GET', '/team/' . self::TEAM_ID, server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::TEAM_ID, $body['id']);
        $this->assertSame('Backend', $body['title']);
        $this->assertSame([], $body['tags']);
        $this->assertCount(1, $body['members']);
        $this->assertSame('member-1', $body['members'][0]['userId']);
        $this->assertSame('member', $body['members'][0]['role']);
        $this->assertSame('member1', $body['members'][0]['user']['username']);
        $this->assertSame([self::USER_ID], $body['owners']);
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
