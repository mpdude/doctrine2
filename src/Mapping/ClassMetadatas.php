<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use ArrayIterator;
use Countable;
use Doctrine\Persistence\Mapping\MappingException;
use Doctrine\Persistence\Mapping\ReflectionService;
use IteratorAggregate;
use Traversable;

use function count;
use function sprintf;

/**
 * A collection of {@see ClassMetadata} instances, indexed and retrievable by class name.
 *
 * @implements IteratorAggregate<class-string, ClassMetadata<object>>
 */
final class ClassMetadatas implements Countable, IteratorAggregate
{
    /** @param array<class-string, ClassMetadata<object>> $classMetadata */
    private function __construct(
        private readonly array $classMetadata,
    ) {
    }

    /**
     * Builds an instance from a list of metadata, such as the one returned by
     * {@see ClassMetadatasBuilder::build()}, keying each entry by its class name.
     *
     * @param list<ClassMetadata<object>> $classMetadata
     */
    public static function fromList(array $classMetadata): self
    {
        $byClassName = [];

        foreach ($classMetadata as $metadata) {
            $byClassName[$metadata->name] = $metadata;
        }

        return new self($byClassName);
    }

    public function has(string $className): bool
    {
        return isset($this->classMetadata[$className]);
    }

    /** @return ClassMetadata<object> */
    public function get(string $className): ClassMetadata
    {
        return $this->classMetadata[$className]
            ?? throw new MappingException(sprintf('No metadata found for class "%s".', $className));
    }

    public function count(): int
    {
        return count($this->classMetadata);
    }

    /** @return Traversable<class-string, ClassMetadata<object>> */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->classMetadata);
    }

    public function wakeupReflection(ReflectionService $reflectionService): void
    {
        foreach ($this->classMetadata as $metadata) {
            $metadata->wakeupReflection($reflectionService);
        }
    }
}
