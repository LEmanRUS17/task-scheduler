<?php

declare(strict_types=1);

namespace App\CommentFeatureApi\DTORequest;

interface UpdateCommentRequestInterface
{
    public function getContent(): string;
}
