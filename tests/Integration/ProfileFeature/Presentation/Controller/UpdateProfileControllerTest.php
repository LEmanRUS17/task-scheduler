<?php

declare(strict_types=1);

namespace App\Tests\Integration\ProfileFeature\Presentation\Controller;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class UpdateProfileControllerTest extends WebTestCase
{
    private const USER_ID = 'aaaaaaaa-aaaa-4aaa-aaaa-aaaaaaaaaaaa';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('PATCH', '/profile/me', server: ['CONTENT_TYPE' => 'application/json'], content: '{}');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdateReturnsUpdatedProfile(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $profileResponse = $this->makeProfileResponse(
            username: 'newname',
            firstname: 'John',
            lastname: 'Doe',
            midlname: null,
            status: 'Active dev',
            lastLogin: new \DateTimeImmutable('2026-01-15T10:00:00+00:00'),
        );

        $service = $this->createMock(ProfileServiceInterface::class);
        $service->expects($this->once())->method('update')->with(self::USER_ID);
        $service->expects($this->once())->method('getByUserId')->with(self::USER_ID)->willReturn($profileResponse);
        static::getContainer()->set(ProfileServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/profile/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user), 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['username' => 'newname', 'firstname' => 'John', 'lastname' => 'Doe', 'status' => 'Active dev']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(self::USER_ID, $body['userId']);
        $this->assertSame('newname', $body['username']);
        $this->assertSame('John', $body['firstname']);
        $this->assertSame('Doe', $body['lastname']);
        $this->assertNull($body['midlname']);
        $this->assertSame('Active dev', $body['status']);
        $this->assertSame('2026-01-15T10:00:00+00:00', $body['lastLogin']);
    }

    public function testUpdateWithTooLongUsernameReturns422(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(ProfileServiceInterface::class);
        $service->expects($this->never())->method('update');
        static::getContainer()->set(ProfileServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/profile/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user), 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['username' => str_repeat('x', 51)]),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function testUpdateWhenServiceThrowsValidationExceptionReturns422(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $violations = ['username' => ['Username must not exceed 50 characters']];
        $service = $this->createMock(ProfileServiceInterface::class);
        $service->expects($this->once())->method('update')
            ->willThrowException(new \InvalidArgumentException(json_encode($violations)));
        static::getContainer()->set(ProfileServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/profile/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user), 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['username' => 'valid']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Validation failed', $body['message']);
        $this->assertArrayHasKey('errors', $body);
        $this->assertArrayHasKey('username', $body['errors']);
    }

    public function testUpdateWhenProfileNotFoundReturns404(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(ProfileServiceInterface::class);
        $service->expects($this->once())->method('update')
            ->willThrowException(new \DomainException('Profile for user ' . self::USER_ID . ' not found'));
        static::getContainer()->set(ProfileServiceInterface::class, $service);

        $client->request(
            'PATCH',
            '/profile/me',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user), 'CONTENT_TYPE' => 'application/json'],
            content: json_encode(['username' => 'test']),
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
        $this->assertStringContainsString(self::USER_ID, $body['message']);
    }

    private function makeProfileResponse(
        ?string $username,
        ?string $firstname,
        ?string $lastname,
        ?string $midlname,
        ?string $status,
        ?\DateTimeImmutable $lastLogin,
    ): ProfileDataResponseInterface {
        $response = $this->createStub(ProfileDataResponseInterface::class);
        $response->method('getUserId')->willReturn(self::USER_ID);
        $response->method('getUsername')->willReturn($username);
        $response->method('getFirstname')->willReturn($firstname);
        $response->method('getLastname')->willReturn($lastname);
        $response->method('getMidlname')->willReturn($midlname);
        $response->method('getStatus')->willReturn($status);
        $response->method('getLastLogin')->willReturn($lastLogin);

        return $response;
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString(self::USER_ID),
            Email::fromString('profile@example.com'),
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

        return $jwtManager->createFromPayload(new SecurityUser($user), ['sub' => $user->email()->value()]);
    }
}
