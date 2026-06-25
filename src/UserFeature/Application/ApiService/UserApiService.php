<?php

declare(strict_types=1);

namespace App\UserFeature\Application\ApiService;

use App\UserFeature\Application\DataMapper\UserDataMapper;
use App\UserFeature\Application\DTORequestValidator\UserValidatorInterface;
use App\UserFeature\Domain\Interactor\ChangePasswordInteractor;
use App\UserFeature\Domain\Interactor\ConfirmUserInteractor;
use App\UserFeature\Domain\Interactor\RegisterUserInteractor;
use App\UserFeature\Domain\Repository\UserRepositoryInterface;
use App\UserFeature\Domain\ValueObject\Email;
use App\UserFeature\Domain\ValueObject\UserId;
use App\UserFeatureApi\DTORequest\ChangePasswordRequestInterface;
use App\UserFeatureApi\DTORequest\ConfirmUserRequestInterface;
use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use App\UserFeatureApi\DTOResponse\UserDataResponseInterface;
use App\UserFeatureApi\Service\UserServiceInterface;

final class UserApiService implements UserServiceInterface
{
    public function __construct(
        private readonly RegisterUserInteractor $registerUserInteractor,
        private readonly ConfirmUserInteractor $confirmUserInteractor,
        private readonly ChangePasswordInteractor $changePasswordInteractor,
        private readonly UserDataMapper $dataMapper,
        private readonly UserValidatorInterface $validator,
        private readonly UserRepositoryInterface $userRepository,
    ) {
    }

    public function register(RegisterUserRequestInterface $request): void
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $email = $this->dataMapper->requestToEmail($request);

        $this->registerUserInteractor->register($email, $request->getPlainPassword());
    }

    public function confirm(ConfirmUserRequestInterface $request): void
    {
        $this->confirmUserInteractor->confirm(
            Email::fromString($request->getEmail()),
            $request->getCode(),
        );
    }

    public function changePassword(string $userId, ChangePasswordRequestInterface $request): void
    {
        $this->changePasswordInteractor->changePassword(
            UserId::fromString($userId),
            $request->getCurrentPassword(),
            $request->getNewPassword(),
        );
    }

    public function findById(string $id): ?UserDataResponseInterface
    {
        $user = $this->userRepository->findById(UserId::fromString($id));

        return $user !== null ? $this->dataMapper->userToResponse($user) : null;
    }
}
