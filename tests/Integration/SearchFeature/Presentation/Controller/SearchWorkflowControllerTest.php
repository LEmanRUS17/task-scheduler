<?php

declare(strict_types=1);

namespace App\Tests\Integration\SearchFeature\Presentation\Controller;

use App\SearchFeatureApi\Contract\SearchServiceInterface;
use App\SearchFeatureApi\DTOResponse\WorkflowSearchResultInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class SearchWorkflowControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('GET', '/workflows/search?q=flow');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testReturnsBadRequestWhenQueryIsTooShort(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $client->request('GET', '/workflows/search?q=f', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('message', $body);
    }

    public function testReturnsResults(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $result = $this->makeResult('wf-1', 'Bug flow');

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchWorkflows')
            ->with('flow', self::USER_ID, false)
            ->willReturn([$result]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/workflows/search?q=flow', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertCount(1, $body['results']);
        $this->assertSame('wf-1', $body['results'][0]['id']);
        $this->assertSame('Bug flow', $body['results'][0]['title']);
    }

    public function testReturnsOnlyOwnedResultsWhenOwnerRequested(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchWorkflows')
            ->with('flow', self::USER_ID, true)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/workflows/search?q=flow&owner=true', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsAllResultsWhenOwnerIsFalse(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createMock(SearchServiceInterface::class);
        $service->expects($this->once())
            ->method('searchWorkflows')
            ->with('flow', self::USER_ID, false)
            ->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/workflows/search?q=flow&owner=false', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
    }

    public function testReturnsEmptyResultsWhenNothingFound(): void
    {
        $user = $this->makeUser(self::USER_ID);
        $client = static::createClient();
        $this->stubUserRepository($user);

        $service = $this->createStub(SearchServiceInterface::class);
        $service->method('searchWorkflows')->willReturn([]);
        static::getContainer()->set(SearchServiceInterface::class, $service);

        $client->request('GET', '/workflows/search?q=nothing', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        $body = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame([], $body['results']);
    }

    private function makeResult(string $workflowId, string $title): WorkflowSearchResultInterface
    {
        $result = $this->createStub(WorkflowSearchResultInterface::class);
        $result->method('getWorkflowId')->willReturn($workflowId);
        $result->method('getTitle')->willReturn($title);

        return $result;
    }

    private function makeUser(string $userId): User
    {
        return User::register(
            UserId::fromString($userId),
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
