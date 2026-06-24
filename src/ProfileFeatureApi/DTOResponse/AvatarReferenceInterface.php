<?php

declare(strict_types=1);

namespace App\ProfileFeatureApi\DTOResponse;

/**
 * A reference to a profile's avatar, exposed inside profile responses so that
 * consumers do not have to issue a separate request to discover whether an
 * avatar exists or how to fetch it. The actual image bytes are still served by
 * the dedicated avatar endpoint; this carries only its URLs.
 */
interface AvatarReferenceInterface
{
    /** URL of the avatar. Size is chosen by the avatar endpoint (defaults to the largest). */
    public function getUrl(): string;
}
