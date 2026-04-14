<?php

declare(strict_types=1);

namespace App\UserFeature\Application\ApiService;

use App\UserFeature\Application\DataMapper\UserDataMapper;
use App\UserFeature\Application\DTORequestValidator\UserValidatorInterface;
use App\UserFeature\Domain\Interactor\RegisterUserInteractor;
use App\UserFeatureApi\DTORequest\RegisterUserRequestInterface;
use App\UserFeatureApi\Service\UserServiceInterface;

final class UserApiService implements UserServiceInterface
{
    public function __construct(
        private readonly RegisterUserInteractor $registerUserInteractor,
        private readonly UserDataMapper $dataMapper,
        private readonly UserValidatorInterface $validator,
    ) {}

    public function register(RegisterUserRequestInterface $request): void
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $email = $this->dataMapper->requestToEmail($request);

        $this->registerUserInteractor->register($email, $request->getPlainPassword());
    }
}
