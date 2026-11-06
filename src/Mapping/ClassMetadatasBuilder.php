<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;
use LogicException;

use function array_unshift;
use function array_values;
use function sprintf;

/**
 * A one-shot service that drives a full metadata build: give it an EntityManager (via
 * {@see setEntityManager()}), call {@see build()}, and it hands back an immutable
 * {@see ClassMetadatas} snapshot of every class known to the mapping driver. That snapshot can be
 * cached and/or used by a {@see CompiledClassMetadataFactory}.
 *
 * Reuses {@see AbstractClassMetadataFactory}'s driver access, inheritance resolution and embedded
 * class handling; its own storage is a plain, mutable array, since an instance is only ever used
 * for a single build() call and then discarded. Extending that class is purely an implementation
 * shortcut to reach its protected machinery, not a genuine is-a relationship: most of its public
 * API (the one inherited from {@see \Doctrine\Persistence\Mapping\ClassMetadataFactory}) is
 * deliberately unsupported here, see the methods below. Callers must not treat this class as a
 * general-purpose metadata factory.
 *
 * @internal
 */
final class ClassMetadatasBuilder extends AbstractClassMetadataFactory
{
    /** @var array<class-string, ClassMetadata<object>> */
    private array $built = [];

    public function build(): ClassMetadatas
    {
        if (! $this->initialized) {
            $this->initialize();
        }

        foreach ($this->getDriver()->getAllClassNames() as $className) {
            $this->buildOne($className);
        }

        return ClassMetadatas::fromList(array_values($this->built));
    }

    public function hasMetadataFor(string $className): bool
    {
        throw $this->unsupported(__FUNCTION__);
    }

    public function getMetadataFor(string $className): ClassMetadata
    {
        return $this->buildOne($this->normalizeClassName($className));
    }

    public function setMetadataFor(string $className, ClassMetadataInterface $class): void
    {
        throw $this->unsupported(__FUNCTION__);
    }

    /** @phpstan-return list<ClassMetadata<object>> */
    public function getAllMetadata(): array
    {
        throw $this->unsupported(__FUNCTION__);
    }

    /** @phpstan-return array<class-string, ClassMetadata<object>> */
    public function getLoadedMetadata(): array
    {
        throw $this->unsupported(__FUNCTION__);
    }

    public function isTransient(string $className): bool
    {
        throw $this->unsupported(__FUNCTION__);
    }

    public function isMapped(string $className): bool
    {
        throw $this->unsupported(__FUNCTION__);
    }

    private function unsupported(string $method): LogicException
    {
        return new LogicException(sprintf(
            '%s::%s() is not supported; this class only drives build(), it is not a general-purpose metadata factory.',
            self::class,
            $method,
        ));
    }

    /**
     * Builds (and memoizes) a single class's metadata, recursively building its ancestors first.
     * Safe to call re-entrantly, e.g. while another class is still being built: a class only
     * needs its own ancestor chain, not the rest of the driver's class list, so calling this for
     * an embedded class from within doLoadMetadata() (see
     * {@see AbstractClassMetadataFactory::doLoadMetadata()}) just builds what is missing so far.
     *
     * @param class-string $className
     *
     * @return ClassMetadata<object>
     */
    private function buildOne(string $className): ClassMetadata
    {
        if (isset($this->built[$className])) {
            return $this->built[$className];
        }

        if (! $this->initialized) {
            $this->initialize();
        }

        $parent          = null;
        $rootEntityFound = false;
        $visited         = [];

        foreach ($this->getParentClasses($className) as $ancestorClassName) {
            $parent = $this->buildOne($ancestorClassName);

            if ($this->isEntity($parent)) {
                $rootEntityFound = true;
                array_unshift($visited, $ancestorClassName);
            }
        }

        $reflService = $this->getReflectionService();
        $class       = $this->newClassMetadataInstance($className);
        $this->initializeReflection($class, $reflService);

        $this->doLoadMetadata($class, $parent, $rootEntityFound, $visited);

        $this->built[$className] = $class;

        $this->wakeupReflection($class, $reflService);

        return $this->built[$className];
    }
}
