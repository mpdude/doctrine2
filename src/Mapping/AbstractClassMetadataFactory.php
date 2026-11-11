<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Deprecations\Deprecation;
use Doctrine\ORM\Utility\OwningSideResolver;
use Doctrine\Persistence\Mapping\ClassMetadata as ClassMetadataInterface;

/**
 * Shared behavior for {@see ClassMetadataFactory} and any other implementation, e.g. a compiled,
 * read-only one, factored out into {@see ClassMetadataLoading}.
 *
 * @internal
 */
abstract class AbstractClassMetadataFactory implements ClassMetadataFactoryInterface
{
    use ClassMetadataLoading;

    /**
     * @deprecated Use {@see OwningSideResolver::resolve()} instead.
     *
     * @param A $maybeOwningSide
     *
     * @return (A is ManyToManyAssociationMapping ? ManyToManyOwningSideMapping : (
     *     A is OneToOneAssociationMapping ? OneToOneOwningSideMapping : (
     *     A is OneToManyAssociationMapping ? ManyToOneAssociationMapping : (
     *     A is ManyToOneAssociationMapping ? ManyToOneAssociationMapping :
     *     ManyToManyOwningSideMapping|OneToOneOwningSideMapping|ManyToOneAssociationMapping
     * ))))
     *
     * @template A of AssociationMapping
     */
    final public function getOwningSide(AssociationMapping $maybeOwningSide): OwningSideMapping
    {
        Deprecation::trigger(
            'doctrine/orm',
            'https://github.com/doctrine/orm/pull/12760',
            '%s is deprecated, use %s::resolve() instead.',
            __METHOD__,
            OwningSideResolver::class,
        );

        return OwningSideResolver::resolve($this, $maybeOwningSide);
    }

    /** @param class-string $className */
    abstract public function isMapped(string $className): bool;

    /** @return list<ClassMetadata<object>> */
    abstract public function getAllMetadata(): array;

    abstract public function hasMetadataFor(string $className): bool;

    abstract public function setMetadataFor(string $className, ClassMetadataInterface $class): void;

    abstract public function isTransient(string $className): bool;
}
