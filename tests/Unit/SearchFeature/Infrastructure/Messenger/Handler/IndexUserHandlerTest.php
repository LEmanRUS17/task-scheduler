<?php

declare(strict_types=1);

namespace App\Tests\Unit\SearchFeature\Infrastructure\Messenger\Handler;

use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;
use App\SearchFeature\Domain\Port\UserSearchIndexInterface;
use App\SearchFeature\Infrastructure\Messenger\Handler\IndexUserHandler;
use App\SearchFeature\Infrastructure\Messenger\Message\IndexUserMessage;
use App\TeamFeatureApi\DTOResponse\TeamDataResponseInterface;
use App\TeamFeatureApi\Service\TeamServiceInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;
use PHPUnit\Framework\TestCase;

final class IndexUserHandlerTest extends TestCase
{
    private function makeUser(string $id = 'user-uuid', string $email = 'john@example.com'): UserDataResponseInterface
    {
        $user = $this->createStub(UserDataResponseInterface::class);
        $user->method('getId')->willReturn($id);
        $user->method('getEmail')->willReturn($email);

        return $user;
    }

    private function makeProfile(
        string $username = 'john_doe',
        ?string $firstname = 'John',
        ?string $lastname = 'Doe',
        ?string $midlname = 'Michael',
    ): ProfileDataResponseInterface {
        $profile = $this->createStub(ProfileDataResponseInterface::class);
        $profile->method('getUsername')->willReturn($username);
        $profile->method('getFirstname')->willReturn($firstname);
        $profile->method('getLastname')->willReturn($lastname);
        $profile->method('getMidlname')->willReturn($midlname);

        return $profile;
    }

    private function makeTeam(string $id): TeamDataResponseInterface
    {
        $team = $this->createStub(TeamDataResponseInterface::class);
        $team->method('getId')->willReturn($id);

        return $team;
    }

    public function testIndexesUserWithProfileAndTeams(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $profileService = $this->createStub(ProfileServiceInterface::class);
        $profileService->method('getByUserId')->willReturn($this->makeProfile());

        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getTeamsByUserId')->willReturn([$this->makeTeam('team-1'), $this->makeTeam('team-2')]);

        $searchIndex = $this->createMock(UserSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('user-uuid', 'john_doe', 'john@example.com', 'John', 'Doe', 'Michael', ['team-1', 'team-2']);

        (new IndexUserHandler($profileService, $userService, $teamService, $searchIndex))(new IndexUserMessage('user-uuid'));
    }

    public function testIndexesUserWithEmptyStringsForMissingProfileFields(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $profileService = $this->createStub(ProfileServiceInterface::class);
        $profileService->method('getByUserId')->willReturn($this->makeProfile(firstname: null, lastname: null, midlname: null));

        $teamService = $this->createStub(TeamServiceInterface::class);
        $teamService->method('getTeamsByUserId')->willReturn([]);

        $searchIndex = $this->createMock(UserSearchIndexInterface::class);
        $searchIndex->expects($this->once())
            ->method('index')
            ->with('user-uuid', 'john_doe', 'john@example.com', '', '', '', []);

        (new IndexUserHandler($profileService, $userService, $teamService, $searchIndex))(new IndexUserMessage('user-uuid'));
    }

    public function testDoesNothingWhenUserNotFound(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn(null);

        $profileService = $this->createStub(ProfileServiceInterface::class);
        $teamService = $this->createStub(TeamServiceInterface::class);

        $searchIndex = $this->createMock(UserSearchIndexInterface::class);
        $searchIndex->expects($this->never())->method('index');

        (new IndexUserHandler($profileService, $userService, $teamService, $searchIndex))(new IndexUserMessage('user-uuid'));
    }

    public function testDoesNothingWhenProfileNotFound(): void
    {
        $userService = $this->createStub(UserServiceInterface::class);
        $userService->method('findById')->willReturn($this->makeUser());

        $profileService = $this->createStub(ProfileServiceInterface::class);
        $profileService->method('getByUserId')->willThrowException(new \DomainException('Profile not found'));

        $teamService = $this->createStub(TeamServiceInterface::class);

        $searchIndex = $this->createMock(UserSearchIndexInterface::class);
        $searchIndex->expects($this->never())->method('index');

        (new IndexUserHandler($profileService, $userService, $teamService, $searchIndex))(new IndexUserMessage('user-uuid'));
    }
}
