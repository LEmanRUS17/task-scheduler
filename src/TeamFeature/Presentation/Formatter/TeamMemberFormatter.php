<?php

declare(strict_types=1);

namespace App\TeamFeature\Presentation\Formatter;

use App\TeamFeatureApi\DTOResponse\TeamMemberDataResponseInterface;

final class TeamMemberFormatter
{
    /**
     * @return array<string, mixed>
     */
    public static function format(TeamMemberDataResponseInterface $member): array
    {

        $profile = $member->getProfile();

        return [
            'userId' => $member->getUserId(),
            'role' => $member->getRole(),
            'joinedAt' => $member->getJoinedAt()->format(\DateTimeInterface::ATOM),
            'user' => $profile === null ? null : [
                'userId' => $profile->getUserId(),
                'username' => $profile->getUsername(),
                'firstname' => $profile->getFirstname(),
                'lastname' => $profile->getLastname(),
                'midlname' => $profile->getMidlname(),
                'status' => $profile->getStatus(),
                'avatar' => $profile->getAvatar()?->getUrl(),
            ],
        ];
    }

    /**
     * Formats a member as just the user identity: id, nickname, ФИО and avatar.
     *
     * @return array<string, mixed>
     */
    public static function formatUser(TeamMemberDataResponseInterface $member): array
    {
        $data = self::format($member);

        unset($data['role'], $data['joinedAt']);

        if ($data['user'] !== null) {
            unset($data['user']['status']);
        }

        return $data;
    }
}
