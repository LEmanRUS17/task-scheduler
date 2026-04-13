<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DataMapper;

use App\ProfileFeature\Domain\Entity\Profile;

final class ProfileDataMapper
{
    public function toResponse(Profile $profile): ProfileDataResponse
    {
        return new ProfileDataResponse(
            userId: $profile->userId(),
            username: $profile->username()?->value(),
            firstname: $profile->firstname(),
            lastname: $profile->lastname(),
            midlname: $profile->midlname(),
            status: $profile->status()?->value(),
            lastLogin: $profile->lastLogin(),
        );
    }
}
