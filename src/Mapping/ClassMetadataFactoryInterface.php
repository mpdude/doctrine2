<?php

declare(strict_types=1);

namespace Doctrine\ORM\Mapping;

use Doctrine\Persistence\Mapping\ClassMetadata as PersistenceClassMetadata;
use Doctrine\Persistence\Mapping\ClassMetadataFactory as PersistenceClassMetadataFactory;
use Doctrine\Persistence\Mapping\MappingException;
use ReflectionException;

/**
 * The ORM-specific contract for a class metadata factory.
 *
 * @extends PersistenceClassMetadataFactory<PersistenceClassMetadata<object>>
 */
interface ClassMetadataFactoryInterface extends PersistenceClassMetadataFactory
{
    /**
     * Gets metadata of all classes known to the underlying mapping driver.
     *
     * @return list<ClassMetadata<object>> The ClassMetadata instances of all mapped classes.
     */
    public function getAllMetadata(): array;

    /**
     * Gets the class metadata descriptor for a class.
     *
     * @param class-string $className The name of the class.
     *
     * @return ClassMetadata<object>
     *
     * @throws MappingException
     * @throws ReflectionException
     */
    public function getMetadataFor(string $className): PersistenceClassMetadata;

    /**
     * Whether this class is mapped by the ORM.
     *
     * In addition to non-transient classes (see {@see isTransient()}), this also includes
     * embedded classes, which provide mapped fields as well.
     *
     * @param class-string $className
     */
    public function isMapped(string $className): bool;

    /**
     * Whether this class is transient, i. e. neither an entity nor a mapped superclass.
     *
     * @param class-string $className
     */
    public function isTransient(string $className): bool;

    /**
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
    public function getOwningSide(AssociationMapping $maybeOwningSide): OwningSideMapping;
}
