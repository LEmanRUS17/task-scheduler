<?php

declare(strict_types=1);

namespace App\Tests\Integration\ProfileFeature\Presentation\Controller;

use App\FileFeatureApi\Contract\FileMetadataInterface;
use App\FileFeatureApi\Contract\FileServiceInterface;
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

final class UploadAvatarControllerTest extends WebTestCase
{
    private const USER_ID = 'a1b2c3d4-e5f6-4a7b-8c9d-0e1f2a3b4c5d';

    public function testReturnsUnauthorizedWhenNotAuthenticated(): void
    {
        $client = static::createClient();
        $client->request('POST', '/profile/me/avatar');

        $this->assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }

    public function testUploadsAvatarAndReturnsMetadata(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $metadata = $this->createStub(FileMetadataInterface::class);
        $metadata->method('getId')->willReturn('file-1');
        $metadata->method('getOriginalName')->willReturn('avatar.png');
        $metadata->method('getMimeType')->willReturn('image/png');
        $metadata->method('getSize')->willReturn(123);

        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->once())
            ->method('uploadAvatar')
            ->willReturn($metadata);
        static::getContainer()->set(FileServiceInterface::class, $fileService);

        $client->request(
            'POST',
            '/profile/me/avatar',
            files: ['file' => $this->makeUploadedFile()],
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $body = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertSame('file-1', $body['id']);
        $this->assertSame('/profile/' . self::USER_ID . '/avatar', $body['url']);
    }

    public function testRejectsMissingFile(): void
    {
        $user = $this->makeUser();
        $client = static::createClient();
        $this->stubUserRepository($user);

        $fileService = $this->createMock(FileServiceInterface::class);
        $fileService->expects($this->never())->method('uploadAvatar');
        static::getContainer()->set(FileServiceInterface::class, $fileService);

        $client->request(
            'POST',
            '/profile/me/avatar',
            server: ['HTTP_AUTHORIZATION' => 'Bearer ' . $this->generateToken($user)],
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
    }

    private function makeUploadedFile(): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'avatar');
        file_put_contents((string) $path, 'binary-image-data');

        return new UploadedFile((string) $path, 'avatar.png', 'image/png', null, true);
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
