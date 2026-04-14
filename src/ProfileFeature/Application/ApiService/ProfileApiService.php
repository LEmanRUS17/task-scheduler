<?php

declare(strict_types=1);

namespace App\ProfileFeature\Application\ApiService;

use App\ProfileFeature\Application\DataMapper\ProfileDataMapper;
use App\ProfileFeature\Application\DTORequestValidator\ProfileValidatorInterface;
use App\ProfileFeature\Domain\Interactor\CreateProfileInteractor;
use App\ProfileFeature\Domain\Interactor\UpdateProfileInteractor;
use App\ProfileFeature\Domain\Repository\ProfileRepositoryInterface;
use App\ProfileFeatureApi\DTORequest\UpdateProfileRequestInterface;
use App\ProfileFeatureApi\DTOResponse\ProfileDataResponseInterface;
use App\ProfileFeatureApi\Service\ProfileServiceInterface;

final class ProfileApiService implements ProfileServiceInterface
{
    public function __construct(
        private readonly CreateProfileInteractor $createProfileInteractor,
        private readonly UpdateProfileInteractor $updateProfileInteractor,
        private readonly ProfileRepositoryInterface $profiles,
        private readonly ProfileDataMapper $dataMapper,
        private readonly ProfileValidatorInterface $validator,
    ) {}

    public function createForUser(string $userId): void
    {
        $this->createProfileInteractor->create($userId);
    }

    public function getByUserId(string $userId): ProfileDataResponseInterface
    {
        $profile = $this->profiles->findByUserId($userId);

        if ($profile === null) {
            throw new \DomainException("Profile for user {$userId} not found");
        }

        return $this->dataMapper->toResponse($profile);
    }

    public function update(string $userId, UpdateProfileRequestInterface $request): void
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations));
        }

        $this->updateProfileInteractor->update(
            $userId,
            $request->getUsername(),
            $request->getFirstname(),
            $request->getLastname(),
            $request->getMidlname(),
            $request->getStatus(),
        );
    }
}
