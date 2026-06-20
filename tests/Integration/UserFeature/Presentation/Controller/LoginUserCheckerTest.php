<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserFeature\Presentation\Controller;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Domain\ValueObject\UserStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class LoginUserCheckerTest extends WebTestCase
{
    public function testDeletedUserCannotLogIn(): void
    {
        $client = static::createClient();

        $user = $this->makeUser();
        $reflection = new \ReflectionProperty(User::class, 'status');
        $reflection->setValue($user, UserStatus::DELETED);

        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($user);
        static::getContainer()->set(UserRepositoryInterface::class, $repo);

        $client->request('POST', '/auth/login', content: json_encode([
            'email' => 'deleted@example.com',
            'password' => 'whatever',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'),
            Email::fromString('deleted@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }
}
