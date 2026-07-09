<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DTORequest;

use App\CommentFeatureApi\DTORequest\UpdateCommentRequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

final class UpdateCommentRequestDTO implements UpdateCommentRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Comment is required')]
        #[Assert\Length(max: 10000, maxMessage: 'Comment must not exceed {{ limit }} characters')]
        private readonly string $comment,
    ) {
    }

    public function getContent(): string
    {
        return $this->comment;
    }
}
