<?php

declare(strict_types=1);

namespace App\Tests\Integration\UserFeature\Presentation\Controller;

use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeatureApi\Service\UserServiceInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Security\Http\Authentication\AuthenticationSuccessHandler;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class ConfirmUserControllerTest extends WebTestCase
{
    public function testConfirmActivatesAndReturnsToken(): void
    {
        $client = static::createClient();

        $service = $this->createMock(UserServiceInterface::class);
        $service->expects($this->once())->method('confirm');
        static::getContainer()->set(UserServiceInterface::class, $service);

        // Let the real UserProvider resolve the user from a stubbed repository.
        $repo = $this->createStub(UserRepositoryInterface::class);
        $repo->method('findByEmail')->willReturn($this->makeUser());
        static::getContainer()->set(UserRepositoryInterface::class, $repo);

        // Avoid relying on JWT keys: stub the success handler.
        $successHandler = $this->createStub(AuthenticationSuccessHandler::class);
        $successHandler->method('handleAuthenticationSuccess')
            ->willReturn(new JsonResponse(['token' => 'jwt-token']));
        static::getContainer()->set('lexik_jwt_authentication.handler.authentication_success', $successHandler);

        $client->request('POST', '/auth/confirm', content: json_encode([
            'email' => 'user@example.com',
            'code' => '654321',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseIsSuccessful();
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('jwt-token', $body['token']);
    }

    public function testConfirmReturnsUnprocessableOnInvalidCode(): void
    {
        $client = static::createClient();

        $service = $this->createStub(UserServiceInterface::class);
        $service->method('confirm')->willThrowException(new \DomainException('Invalid confirmation code'));
        static::getContainer()->set(UserServiceInterface::class, $service);

        $client->request('POST', '/auth/confirm', content: json_encode([
            'email' => 'user@example.com',
            'code' => '000000',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertFalse($body['success']);
    }

    public function testConfirmReturnsUnprocessableWhenCodeMissing(): void
    {
        $client = static::createClient();

        $client->request('POST', '/auth/confirm', content: json_encode([
            'email' => 'user@example.com',
        ]), server: ['CONTENT_TYPE' => 'application/json']);

        $this->assertResponseStatusCodeSame(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    private function makeUser(): User
    {
        return User::register(
            UserId::fromString('a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d'),
            Email::fromString('user@example.com'),
            HashedPassword::fromHash('$2y$04$dummyhashfortestingpurposesonly123456'),
            new \DateTimeImmutable(),
        );
    }
}
