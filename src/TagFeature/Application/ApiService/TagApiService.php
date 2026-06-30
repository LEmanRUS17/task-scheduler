<?php

declare(strict_types=1);

namespace App\TagFeature\Application\ApiService;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeature\Application\DataMapper\TagDataMapper;
use App\TagFeature\Application\DTORequestValidator\TagValidatorInterface;
use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Interactor\AssignTagInteractor;
use App\TagFeature\Domain\Interactor\CreateTagInteractor;
use App\TagFeature\Domain\Interactor\DeleteTagInteractor;
use App\TagFeature\Domain\Interactor\UnassignTagInteractor;
use App\TagFeature\Domain\Interactor\UpdateTagInteractor;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use App\TagFeature\Domain\ValueObject\TaggableType;
use App\TagFeatureApi\Contract\TagServiceInterface;
use App\TagFeatureApi\DTORequest\CreateTagRequestInterface;
use App\TagFeatureApi\DTORequest\UpdateTagRequestInterface;
use App\TagFeatureApi\DTOResponse\TagResponseInterface;

final class TagApiService implements TagServiceInterface
{
    public function __construct(
        private readonly CreateTagInteractor $createInteractor,
        private readonly UpdateTagInteractor $updateInteractor,
        private readonly DeleteTagInteractor $deleteInteractor,
        private readonly AssignTagInteractor $assignInteractor,
        private readonly UnassignTagInteractor $unassignInteractor,
        private readonly TagRepositoryInterface $tags,
        private readonly TagAssignmentRepositoryInterface $assignments,
        private readonly TagDataMapper $dataMapper,
        private readonly TagValidatorInterface $validator,
        private readonly DescriptionServiceInterface $descriptions,
    ) {
    }

    public function create(CreateTagRequestInterface $request, string $ownerId): TagResponseInterface
    {
        $this->guardValid($request);

        $tag = $this->createInteractor->create(
            $ownerId,
            TagName::fromString($request->getName()),
            TagColor::fromString($request->getColor()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Tag::class, $tag->id()->value(), $description);
        }

        return $this->dataMapper->tagToResponse($tag, $description);
    }

    public function update(string $id, UpdateTagRequestInterface $request, string $ownerId): ?TagResponseInterface
    {
        $tag = $this->tags->findById(TagId::fromString($id));
        if ($tag === null || $tag->ownerId() !== $ownerId) {
            return null;
        }

        $this->guardValid($request);

        $tag = $this->updateInteractor->update(
            $tag->id(),
            TagName::fromString($request->getName()),
            TagColor::fromString($request->getColor()),
        );

        $description = $request->getDescription();
        if ($description !== null) {
            $this->descriptions->set(Tag::class, $tag->id()->value(), $description);
        }

        return $this->dataMapper->tagToResponse(
            $tag,
            $this->descriptions->get(Tag::class, $tag->id()->value()),
        );
    }

    public function delete(string $id, string $ownerId): bool
    {
        $tag = $this->tags->findById(TagId::fromString($id));
        if ($tag === null || $tag->ownerId() !== $ownerId) {
            return false;
        }

        foreach ($this->assignments->findByTag($tag->id()) as $assignment) {
            $this->unassignInteractor->unassign(
                $assignment->tagId(),
                $assignment->entityType(),
                $assignment->entityId(),
            );
        }

        $this->descriptions->delete(Tag::class, $tag->id()->value());
        $this->deleteInteractor->delete($tag);

        return true;
    }

    public function getById(string $id): ?TagResponseInterface
    {
        $tag = $this->tags->findById(TagId::fromString($id));

        return $tag !== null ? $this->toResponse($tag) : null;
    }

    /** @return TagResponseInterface[] */
    public function getMyTagsPage(string $ownerId, int $limit, int $offset): array
    {
        return array_map(
            fn(Tag $tag) => $this->toResponse($tag),
            $this->tags->findByOwnerPaginated($ownerId, $limit, $offset),
        );
    }

    /** @return TagResponseInterface[] */
    public function getList(): array
    {
        return array_map(
            fn(Tag $tag) => $this->toResponse($tag),
            $this->tags->findAll(),
        );
    }

    /**
     * Returns tags for the given ids, preserving the order of the ids.
     *
     * @param list<string> $ids
     * @return TagResponseInterface[]
     */
    public function getByIds(array $ids): array
    {
        $byId = [];
        foreach ($this->tags->findByIds($ids) as $tag) {
            $byId[$tag->id()->value()] = $tag;
        }

        $result = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $result[] = $this->toResponse($byId[$id]);
            }
        }

        return $result;
    }

    public function countMyTags(string $ownerId): int
    {
        return $this->tags->countByOwner($ownerId);
    }

    public function assign(string $tagId, string $entityType, string $entityId, string $assignedBy): void
    {
        $this->assignInteractor->assign(
            TagId::fromString($tagId),
            TaggableType::fromString($entityType),
            $entityId,
            $assignedBy,
        );
    }

    public function unassign(string $tagId, string $entityType, string $entityId): void
    {
        $this->unassignInteractor->unassign(
            TagId::fromString($tagId),
            TaggableType::fromString($entityType),
            $entityId,
        );
    }

    /** @return TagResponseInterface[] */
    public function getEntityTags(string $entityType, string $entityId): array
    {
        $ids = array_map(
            static fn($assignment) => $assignment->tagId()->value(),
            $this->assignments->findByEntity(TaggableType::fromString($entityType), $entityId),
        );

        return $this->mapTagIds($ids);
    }

    /**
     * Returns the distinct tags assigned to any of the given entities.
     *
     * @param list<string> $entityIds
     * @return TagResponseInterface[]
     */
    public function getTagsForEntities(string $entityType, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $ids = $this->assignments->findTagIdsByEntityIds(TaggableType::fromString($entityType), $entityIds);

        return $this->mapTagIds($ids);
    }

    /**
     * Returns the ids of entities of the given type that carry the given tag.
     *
     * @return list<string>
     */
    public function getEntityIdsByTag(string $entityType, string $tagId): array
    {
        return $this->assignments->findEntityIdsByTag(
            TaggableType::fromString($entityType),
            TagId::fromString($tagId),
        );
    }

    /** @return list<string> */
    public function getEntityTagNames(string $entityType, string $entityId): array
    {
        return array_values(array_map(
            static fn(TagResponseInterface $tag) => $tag->getName(),
            $this->getEntityTags($entityType, $entityId),
        ));
    }

    /** @return list<string> */
    public function filterExistingTagIds(array $tagIds): array
    {
        if ($tagIds === []) {
            return [];
        }

        return array_values(array_map(
            static fn(Tag $tag) => $tag->id()->value(),
            $this->tags->findByIds(array_values(array_unique($tagIds))),
        ));
    }

    /** @return array<string, list<TagResponseInterface>> */
    public function getEntityTagsByIds(string $entityType, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        $grouped = $this->assignments->findTagIdsByEntityIdsGrouped(
            TaggableType::fromString($entityType),
            $entityIds,
        );

        if ($grouped === []) {
            return [];
        }

        $tagsById = [];
        foreach ($this->tags->findByIds(array_merge(...array_values($grouped))) as $tag) {
            // Description is not part of the list payload, so it is not loaded here.
            $tagsById[$tag->id()->value()] = $this->dataMapper->tagToResponse($tag, null);
        }

        $result = [];
        foreach ($grouped as $entityId => $tagIds) {
            $tags = [];
            foreach ($tagIds as $tagId) {
                if (isset($tagsById[$tagId])) {
                    $tags[] = $tagsById[$tagId];
                }
            }
            $result[$entityId] = $tags;
        }

        return $result;
    }

    private function toResponse(Tag $tag): TagResponseInterface
    {
        return $this->dataMapper->tagToResponse(
            $tag,
            $this->descriptions->get(Tag::class, $tag->id()->value()),
        );
    }

    /**
     * @param list<string> $ids
     * @return TagResponseInterface[]
     */
    private function mapTagIds(array $ids): array
    {
        return array_map(
            fn(Tag $tag) => $this->toResponse($tag),
            $this->tags->findByIds($ids),
        );
    }

    private function guardValid(object $request): void
    {
        $violations = $this->validator->validate($request);

        if (!empty($violations)) {
            throw new \InvalidArgumentException(json_encode($violations) ?: '{}');
        }
    }
}
