<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Persistence\Mapping\MappingException as PersistenceMappingException;

/**
 * The ClassMetadataFactory is used to create ClassMetadata objects that contain all the
 * metadata mapping information of a class which describes how a class should be mapped
 * to a relational database.
 */
class ClassMetadataFactory extends AbstractClassMetadataFactory
{
    /** @var array<class-string, bool> */
    private array $mappedClasses = [];

    /**
     * Whether this class is mapped by the ORM.
     *
     * In addition to non-transient classes (see {@see isTransient()}), this also includes
     * embedded classes, which provide mapped fields as well.
     *
     * @param class-string $className
     */
    public function isMapped(string $className): bool
    {
        /*
            With the current state of drivers, finding a reliable answer with regard to Embeddable classes would be to do a full,
            expensive metadata loading attempt. So, try to shortcut this as much as possible by caching the result, referring to the
            cache of already-loaded metadata and separating transient classes (a rather simple class attribute check in the best case)
            before falling through to the load attempt as a last resort.
        */

        return $this->mappedClasses[$className]
            ??= $this->hasMetadataFor($className)
            || ! $this->isTransient($className)
            || $this->canLoadMetadataFor($className);
    }

    /** @param class-string $className */
    private function canLoadMetadataFor(string $className): bool
    {
        try {
            $this->getMetadataFor($className);

            return true;
        } catch (PersistenceMappingException) {
            return false;
        }
    }
}
