<?php

declare(strict_types=1);

namespace App\UserFeatureApi\Service;

use App\UserFeatureApi\DTORequest\ChangePasswordRequestInterface;
use App\UserFeatureApi\DTORequest\ConfirmUserRequestInterface;
use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use App\UserFeatureApi\DTORequest\RequestPasswordResetRequestInterface;
use App\UserFeatureApi\DTORequest\ResetPasswordRequestInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;

interface UserServiceInterface
{
    public function register(RegisterUserRequestInterface $request): void;

    public function confirm(ConfirmUserRequestInterface $request): void;

    public function changePassword(string $userId, ChangePasswordRequestInterface $request): void;

    public function requestPasswordReset(RequestPasswordResetRequestInterface $request): void;

    public function resetPassword(ResetPasswordRequestInterface $request): void;

    public function findById(string $id): ?UserDataResponseInterface;

    public function findByEmail(string $email): ?UserDataResponseInterface;
}
