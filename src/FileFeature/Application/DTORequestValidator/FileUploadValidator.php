<?php

declare(strict_types=1);

namespace App\FileFeature\Application\DTORequestValidator;

use App\FileFeature\Domain\ValueObject\FilePurpose;

final class FileUploadValidator
{
    /**
     * @param list<string> $avatarMimeTypes
     * @param list<string> $attachmentMimeTypes
     */
    public function __construct(
        private readonly int $avatarMaxSize,
        private readonly array $avatarMimeTypes,
        private readonly int $attachmentMaxSize,
        private readonly array $attachmentMimeTypes,
    ) {
    }

    /**
     * @return array<string, list<string>> violations keyed by field, empty when valid
     */
    public function validate(FilePurpose $purpose, string $mimeType, int $size): array
    {
        [$maxSize, $allowedMimeTypes] = match ($purpose) {
            FilePurpose::Avatar => [$this->avatarMaxSize, $this->avatarMimeTypes],
            FilePurpose::Attachment => [$this->attachmentMaxSize, $this->attachmentMimeTypes],
        };

        $violations = [];

        if ($size <= 0) {
            $violations['file'][] = 'The uploaded file is empty.';
        }

        if ($size > $maxSize) {
            $violations['file'][] = sprintf(
                'The file is too large (%d bytes). Maximum allowed size is %d bytes.',
                $size,
                $maxSize,
            );
        }

        if (!in_array($mimeType, $allowedMimeTypes, true)) {
            $violations['file'][] = sprintf(
                'Files of type "%s" are not allowed. Allowed types: %s.',
                $mimeType,
                implode(', ', $allowedMimeTypes),
            );
        }

        return $violations;
    }
}
