<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserFeature\Presentation\Controller;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class RefreshTokenControllerTest extends WebTestCase
{
    public function testReturnsUnauthorizedWhenTokenIsMissing(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/token/refresh', server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedWhenTokenNotFound(): void
    {
        $client = static::createClient();

        $manager = $this->createStub(RefreshTokenManagerInterface::class);
        $manager->method('get')->willReturn(null);
        static::getContainer()->set(RefreshTokenManagerInterface::class, $manager);

        $client->request('POST', '/auth/token/refresh', content: json_encode([
            'refresh_token' => 'unknown-token',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsUnauthorizedWhenTokenIsExpired(): void
    {
        $client = static::createClient();

        $expiredToken = $this->createStub(RefreshTokenInterface::class);
        $expiredToken->method('isValid')->willReturn(false);
        $expiredToken->method('getRefreshToken')->willReturn('expired-token');

        $manager = $this->createStub(RefreshTokenManagerInterface::class);
        $manager->method('get')->willReturn($expiredToken);
        static::getContainer()->set(RefreshTokenManagerInterface::class, $manager);

        $client->request('POST', '/auth/token/refresh', content: json_encode([
            'refresh_token' => 'expired-token',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsNewJwtAndRefreshTokenOnSuccess(): void
    {
        $userId = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';
        $email = 'refresh@example.com';
        $user = $this->makeUser($userId, $email);

        $client = static::createClient();
        $this->stubUserRepository($user);

        $existingToken = $this->createStub(RefreshTokenInterface::class);
        $existingToken->method('isValid')->willReturn(true);
        $existingToken->method('getRefreshToken')->willReturn('valid-token');
        $existingToken->method('getUsername')->willReturn($email);

        $newToken = $this->createStub(RefreshTokenInterface::class);
        $newToken->method('getRefreshToken')->willReturn('new-refresh-token');

        $manager = $this->createMock(RefreshTokenManagerInterface::class);
        $manager->method('get')->willReturn($existingToken);
        $manager->expects($this->once())->method('delete')->with($existingToken);
        $manager->expects($this->once())->method('save')->with($newToken);
        static::getContainer()->set(RefreshTokenManagerInterface::class, $manager);

        $generator = $this->createMock(RefreshTokenGeneratorInterface::class);
        $generator->expects($this->once())->method('createForUserWithTtl')->willReturn($newToken);
        static::getContainer()->set(RefreshTokenGeneratorInterface::class, $generator);

        $client->request('POST', '/auth/token/refresh', content: json_encode([
            'refresh_token' => 'valid-token',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('token', $body);
        $this->assertArrayHasKey('refresh_token', $body);
        $this->assertNotEmpty($body['token']);
        $this->assertSame('new-refresh-token', $body['refresh_token']);
    }

    private function makeUser(string $userId, string $email): User
    {
        return User::register(
            UserId::fromString($userId),
            Email::fromString($email),
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
}
