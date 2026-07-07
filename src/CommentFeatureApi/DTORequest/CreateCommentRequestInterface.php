<?php

declare(strict_types=1);

namespace App\CommentFeatureApi\DTORequest;

interface CreateCommentRequestInterface
{
    public function getContent(): string;
}
