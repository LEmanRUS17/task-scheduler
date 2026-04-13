<?php

declare(strict_types=1);

namespace App\UserFeature\Application\DataMapper;

use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;

final class UserDataMapper
{
    public function requestToEmail(RegisterUserRequestInterface $request): Email
    {
        return Email::fromString($request->getEmail());
    }
}
