<?php

declare(strict_types=1);

namespace App\CommentFeature\Application\DTORequest;

use App\CommentFeatureApi\DTORequest\CreateCommentRequestInterface;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

final class CreateCommentRequestDTO implements CreateCommentRequestInterface
{
    public function __construct(
        #[Assert\NotBlank(message: 'Comment is required')]
        #[Assert\Length(max: 10000, maxMessage: 'Comment must not exceed {{ limit }} characters')]
        private readonly string $comment,
        #[SerializedName('parent_id')]
        #[Assert\Uuid(message: 'Parent ID must be a valid UUID')]
        private readonly ?string $parentId = null,
    ) {
    }

    public function getContent(): string
    {
        return $this->comment;
    }

    public function getParentId(): ?string
    {
        return $this->parentId;
    }
}
