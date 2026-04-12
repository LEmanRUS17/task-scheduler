<?php

declare(strict_types=1);

namespace App\User\Infrastructure\Http\Controller;

use App\User\Application\Command\RegisterUser\RegisterUserCommand;
use App\User\Infrastructure\Http\Request\RegisterUserRequest;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Messenger\Exception\HandlerFailedException;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
final class RegisterUserController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
    ) {}

    #[Route('/auth/register', name: 'user_register', methods: ['POST'])]
    public function __invoke(
        #[MapRequestPayload] RegisterUserRequest $request,
    ): JsonResponse {
        try {
            $this->bus->dispatch(new RegisterUserCommand(
                $request->email,
                $request->plainPassword,
            ));
        } catch (HandlerFailedException $e) {
            $cause = $e->getPrevious();

            if ($cause instanceof \DomainException) {

                return new JsonResponse(
                    ['error' => $cause->getMessage()],
                    Response::HTTP_CONFLICT,
                );
            }

            throw $e;
        }

        return new JsonResponse(
            ['success' => true],
            Response::HTTP_CREATED
        );
    }
}
