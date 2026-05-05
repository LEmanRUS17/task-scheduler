<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DataMapper;

use App\UserFeature\Application\DTOResponse\UserResponseDTO;
use App\UserFeature\Domain\Entity\User;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;

final class UserDataMapper
{
    public function requestToEmail(RegisterUserRequestInterface $request): Email
    {
        return Email::fromString($request->getEmail());
    }

    public function userToResponse(User $user): UserResponseDTO
    {
        return new UserResponseDTO($user->id()->value(), $user->email()->value());
    }
}
