<?php

declare(strict_types=1);

namespace App\CommentFeatureApi\DTORequest;

interface CreateCommentRequestInterface
{
    public function getContent(): string;

    /**
     * Id of the comment being replied to, or null when this is a root comment.
     */
    public function getParentId(): ?string;
}
