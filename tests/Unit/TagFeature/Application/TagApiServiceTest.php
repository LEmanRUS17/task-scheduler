<?php

declare(strict_types=1);

namespace App\Tests\Unit\TagFeature\Application;

use App\DescriptionFeatureApi\Contract\DescriptionServiceInterface;
use App\TagFeature\Application\ApiService\TagApiService;
use App\TagFeature\Application\DataMapper\TagDataMapper;
use App\TagFeature\Application\DTORequest\CreateTagRequestDTO;
use App\TagFeature\Application\DTORequestValidator\TagValidatorInterface;
use App\TagFeature\Domain\Entity\Tag;
use App\TagFeature\Domain\Entity\TagAssignment;
use App\TagFeature\Domain\Interactor\AssignTagInteractor;
use App\TagFeature\Domain\Interactor\CreateTagInteractor;
use App\TagFeature\Domain\Interactor\DeleteTagInteractor;
use App\TagFeature\Domain\Interactor\UnassignTagInteractor;
use App\TagFeature\Domain\Interactor\UpdateTagInteractor;
use App\TagFeature\Domain\Port\ClockInterface;
use App\TagFeature\Domain\Port\DomainEventDispatcherInterface;
use App\TagFeature\Domain\Repository\TagAssignmentRepositoryInterface;
use App\TagFeature\Domain\Repository\TagRepositoryInterface;
use App\TagFeature\Domain\ValueObject\TagColor;
use App\TagFeature\Domain\ValueObject\TagId;
use App\TagFeature\Domain\ValueObject\TagName;
use App\TagFeature\Domain\ValueObject\TaggableType;
use PHPUnit\Framework\TestCase;

final class TagApiServiceTest extends TestCase
{
    /**
     * The interactors are final and exercise only stubbed repositories; the tested code
     * paths short-circuit before reaching them, so their dependencies are throwaway stubs.
     *
     * @param array<string, mixed> $overrides
     */
    private function buildService(array $overrides = []): TagApiService
    {
        $repoStub = $this->createStub(TagRepositoryInterface::class);
        $assignmentStub = $this->createStub(TagAssignmentRepositoryInterface::class);
        $dispatcher = $this->createStub(DomainEventDispatcherInterface::class);
        $clock = $this->createStub(ClockInterface::class);
        $clock->method('now')->willReturn(new \DateTimeImmutable('2024-01-01 12:00:00'));

        return new TagApiService(
            new CreateTagInteractor($repoStub, $dispatcher, $clock),
            new UpdateTagInteractor($repoStub, $dispatcher),
            new DeleteTagInteractor($repoStub, $dispatcher),
            new AssignTagInteractor($repoStub, $assignmentStub, $dispatcher, $clock),
            new UnassignTagInteractor($assignmentStub, $dispatcher),
            $overrides['tags'] ?? $repoStub,
            $overrides['assignments'] ?? $assignmentStub,
            new TagDataMapper(),
            $overrides['validator'] ?? $this->createStub(TagValidatorInterface::class),
            $overrides['descriptions'] ?? $this->createStub(DescriptionServiceInterface::class),
        );
    }

    private function makeTag(string $name): Tag
    {
        return Tag::create(
            TagId::generate(),
            'owner-1',
            TagName::fromString($name),
            TagColor::fromString('#ff0000'),
            new \DateTimeImmutable('2024-01-01 12:00:00'),
        );
    }

    public function testCreateThrowsOnValidationViolations(): void
    {
        $validator = $this->createStub(TagValidatorInterface::class);
        $validator->method('validate')->willReturn(['name' => ['Name is required']]);

        $service = $this->buildService(['validator' => $validator]);

        $this->expectException(\InvalidArgumentException::class);

        $service->create(new CreateTagRequestDTO('', '#ff0000'), 'owner-1');
    }

    public function testGetEntityTagNamesReturnsNames(): void
    {
        $tagA = $this->makeTag('urgent');
        $tagB = $this->makeTag('blocker');

        $assignmentA = TagAssignment::create(
            'a1',
            $tagA->id(),
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
            new \DateTimeImmutable(),
        );
        $assignmentB = TagAssignment::create(
            'a2',
            $tagB->id(),
            TaggableType::fromString('task'),
            'task-1',
            'user-1',
            new \DateTimeImmutable(),
        );

        $assignments = $this->createStub(TagAssignmentRepositoryInterface::class);
        $assignments->method('findByEntity')->willReturn([$assignmentA, $assignmentB]);

        $tags = $this->createStub(TagRepositoryInterface::class);
        $tags->method('findByIds')->willReturn([$tagA, $tagB]);

        $service = $this->buildService(['assignments' => $assignments, 'tags' => $tags]);

        $names = $service->getEntityTagNames('task', 'task-1');

        $this->assertSame(['urgent', 'blocker'], $names);
    }
}
