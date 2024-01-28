<?php

declare(strict_types=1);

namespace Doctrine\ORM\Query\Exec;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;

/**
 * SingleSelectSqlFinalizer finalizes a given SQL query by applying
 * the query's firstResult/maxResult values as well as extra read lock/write lock
 * statements, both through the platform-specific methods.
 *
 * The resulting, "finalized" SQL is passed to a FinalizedSelectExecutor.
 */
class SingleSelectSqlFinalizer implements SqlFinalizer
{
    /** @var string */
    private $sql;

    public function __construct(string $sql)
    {
        $this->sql = $sql;
    }

    /**
     * This method exists temporarily to support old SqlWalker interfaces.
     *
     * @internal
     * @psalm-internal Doctrine\ORM\Query
     */
    public function finalizeSql(Query $query): string
    {
        $platform = $query->getEntityManager()->getConnection()->getDatabasePlatform();

        $sql = $platform->modifyLimitQuery($this->sql, $query->getMaxResults(), $query->getFirstResult());

        $lockMode = $query->getHint(Query::HINT_LOCK_MODE) ?: LockMode::NONE;

        if ($lockMode !== LockMode::NONE && $lockMode !== LockMode::OPTIMISTIC && $lockMode !== LockMode::PESSIMISTIC_READ && $lockMode !== LockMode::PESSIMISTIC_WRITE) {
            throw QueryException::invalidLockMode();
        }

        if ($lockMode === LockMode::PESSIMISTIC_READ) {
            $sql .= ' ' . $platform->getReadLockSQL();
        } elseif ($lockMode === LockMode::PESSIMISTIC_WRITE) {
            $sql .= ' ' . $platform->getWriteLockSQL();
        }

        return $sql;
    }

    public function createExecutor(Query $query): AbstractSqlExecutor
    {
        return new FinalizedSelectExecutor($this->finalizeSql($query));
    }
}
