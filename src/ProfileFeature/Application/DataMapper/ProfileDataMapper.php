<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\DataMapper;

use App\FileFeatureApi\Contract\FileServiceInterface;
use App\ProfileFeature\Domain\Entity\Profile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class ProfileDataMapper
{
    public function __construct(
        private readonly FileServiceInterface $fileService,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

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
            avatar: $this->buildAvatar($profile->userId()),
        );
    }

    private function buildAvatar(string $userId): ?AvatarReference
    {
        if ($this->fileService->getAvatar(Profile::class, $userId) === null) {
            return null;
        }

        return new AvatarReference(
            $this->urlGenerator->generate('profile_avatar_get', ['userId' => $userId]),
        );
    }
}
