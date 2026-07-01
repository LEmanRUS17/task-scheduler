<?php

declare(strict_types=1);

namespace App\Tests\Integration\TagFeature\Presentation\Controller;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Port\TeamMembershipInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class TagControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testListRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/tag');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testListReturnsOwnTagsPaginated(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->expects($this->once())
            ->method('findByOwnerPaginated')
            ->with(self::USER_ID, 10, 0)
            ->willReturn([$this->makeTag('urgent')]);
        $tags->method('countByOwner')->willReturn(1);
        static::getContainer()->set(TagRepositoryInterface::class, $tags);

        $descriptions = $this->createStub(DescriptionServiceInterface::class);
        $descriptions->method('get')->willReturn(null);
        static::getContainer()->set(DescriptionServiceInterface::class, $descriptions);

        $client->request('GET', '/tag', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['tags']);
        $this->assertSame('urgent', $body['tags'][0]['name']);
        $this->assertSame('#ff0000', $body['tags'][0]['color']);
        $this->assertSame(['page' => 1, 'limit' => 10, 'pages' => 1], $body['pagination']);
        $this->assertSame(1, $body['count']);
    }

    public function testCreateReturnsCreatedTag(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $tags = $this->createMock(TagRepositoryInterface::class);
        $tags->method('findByOwnerAndName')->willReturn(null);
        $tags->expects($this->once())->method('save');
        static::getContainer()->set(TagRepositoryInterface::class, $tags);

        $descriptions = $this->createStub(DescriptionServiceInterface::class);
        static::getContainer()->set(DescriptionServiceInterface::class, $descriptions);

        $client->request('POST', '/tag', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode(['name' => 'urgent', 'color' => '#FF0000']));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('urgent', $body['name']);
        $this->assertSame('#ff0000', $body['color']);
        $this->assertSame(self::USER_ID, $body['ownerId']);
    }

    public function testCreateAcceptsDescription(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findByOwnerAndName')->willReturn(null);
        static::getContainer()->set(TagRepositoryInterface::class, $tags);

        $descriptions = $this->createMock(DescriptionServiceInterface::class);
        $descriptions->expects($this->once())
            ->method('set')
            ->with(Tag::class, $this->anything(), 'Critical issues only');
        static::getContainer()->set(DescriptionServiceInterface::class, $descriptions);

        $client->request('POST', '/tag', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode([
            'name' => 'urgent',
            'color' => '#FF0000',
            'description' => 'Critical issues only',
        ]));

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Critical issues only', $body['description']);
    }

    public function testCreateRejectsInvalidColor(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $client->request('POST', '/tag', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
            'CONTENT_TYPE' => 'application/json',
        ], content: json_encode(['name' => 'urgent', 'color' => 'red']));

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testTeamTaskTagsForbiddenForNonMember(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $membership = $this->createStub(TeamMembershipInterface::class);
        $membership->method('isMember')->willReturn(false);
        static::getContainer()->set(TeamMembershipInterface::class, $membership);

        $client->request('GET', '/teams/team-1/task-tags', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function makeTag(string $name): Tag
    {
        return Tag::create(
            TagId::generate(),
            self::USER_ID,
            TagName::fromString($name),
            TagColor::fromString('#ff0000'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
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
