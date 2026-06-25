<?php

declare(strict_types=1);

namespace App\Tests\Integration\TaskFeature\Presentation\Controller;

use App\FileFeatureApi\Contract\FileMetadataInterface;
use App\FileFeatureApi\Contract\FileServiceInterface;
use App\TaskFeatureApi\DTOResponse\TaskDataResponseInterface;
use App\TaskFeatureApi\Service\TaskServiceInterface;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\HashedPassword;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeature\Infrastructure\Security\SecurityUser;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

final class ListTaskFilesControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsFilesWithCount(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn('task-1');
        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($task);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->once())
            ->method('listImageAttachments')
            ->willReturn([$this->image('img-1', 'a.png'), $this->image('img-2', 'b.webp')]);
        static::getContainer()->set(FileServiceInterface::class, $fileService);

        $client->request('GET', '/task/task-1/files', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseIsSuccessful();
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame(2, $body['count']);
        $this->assertCount(2, $body['files']);
        $this->assertSame('img-1', $body['files'][0]['id']);
        $this->assertSame('/task/task-1/attachments/img-2', $body['files'][1]['url']);
    }

    public function testReturnsNotFoundWhenTaskMissing(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn(null);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);

        $client->request('GET', '/task/missing/files', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user),
        ]);

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function image(string $id, string $name): FileMetadataInterface
    {
        $meta = $this->createStub(FileMetadataInterface::class);
        $meta->method('getId')->willReturn($id);
        $meta->method('getOriginalName')->willReturn($name);
        $meta->method('getMimeType')->willReturn('image/png');
        $meta->method('getSize')->willReturn(256);
        $meta->method('getCreatedAt')->willReturn(new \DateTimeImmutable('2026-06-24 10:00:00'));

        return $meta;
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
