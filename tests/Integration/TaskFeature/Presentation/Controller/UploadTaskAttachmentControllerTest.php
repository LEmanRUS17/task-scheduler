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
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

final class UploadTaskAttachmentControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testCreatorCanUploadAttachment(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $this->stubTaskService(createdBy: self::USER_ID);

        $metadata = $this->createStub(FileMetadataInterface::class);
        $metadata->method('getId')->willReturn('file-9');
        $metadata->method('getOriginalName')->willReturn('report.pdf');
        $metadata->method('getMimeType')->willReturn('application/pdf');
        $metadata->method('getSize')->willReturn(2048);

        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->once())->method('attach')->willReturn($metadata);
        static::getContainer()->set(FileServiceInterface::class, $fileService);

        $client->request(
            'POST',
            '/task/task-1/attachments',
            files: ['file' => $this->makeUploadedFile()],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('file-9', $body['id']);
        $this->assertSame('/task/task-1/attachments/file-9', $body['url']);
    }

    public function testNonMemberIsForbidden(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $this->stubTaskService(createdBy: 'someone-else');

        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->never())->method('attach');
        static::getContainer()->set(FileServiceInterface::class, $fileService);

        $client->request(
            'POST',
            '/task/task-1/attachments',
            files: ['file' => $this->makeUploadedFile()],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    private function stubTaskService(string $createdBy): void
    {
        $task = $this->createStub(TaskDataResponseInterface::class);
        $task->method('getId')->willReturn('task-1');
        $task->method('getCreatedBy')->willReturn($createdBy);
        $task->method('getAssigneeIds')->willReturn([]);

        $taskService = $this->createStub(TaskServiceInterface::class);
        $taskService->method('getById')->willReturn($task);
        static::getContainer()->set(TaskServiceInterface::class, $taskService);
    }

    private function makeUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'att');
        file_put_contents((string) $path, '%PDF-1.4 test');

        return new UploadedFile((string) $path, 'report.pdf', 'application/pdf', null, true);
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
