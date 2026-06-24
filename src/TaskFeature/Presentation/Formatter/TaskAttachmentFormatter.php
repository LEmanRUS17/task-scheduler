<?php

declare(strict_types=1);

namespace App\TaskFeature\Presentation\Formatter;

use App\FileFeatureApi\Contract\FileMetadataInterface;

final class TaskAttachmentFormatter
{
    /** @return array<string, mixed> */
    public static function format(string $taskId, FileMetadataInterface $file): array
    {
        return [
            'id' => $file->getId(),
            'name' => $file->getOriginalName(),
            'type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'createdAt' => $file->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'url' => '/task/' . $taskId . '/attachments/' . $file->getId(),
        ];
    }
}
