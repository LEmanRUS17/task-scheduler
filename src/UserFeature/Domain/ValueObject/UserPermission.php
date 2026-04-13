<?php

declare(strict_types=1);

namespace App\UserFeature\Domain\ValueObject;

final class UserPermission
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';
}
