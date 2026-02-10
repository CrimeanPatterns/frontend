<?php

namespace AwardWallet\MainBundle\Globals\Utils\stmt;

use AwardWallet\MainBundle\Globals\Utils\IteratorFluent;
use Doctrine\DBAL\Driver\Statement;

/**
 * @param \PDOStatement|Statement $stmt
 */
function stmt($stmt): IteratorFluent
{
    $stmt->setFetchMode(\PDO::FETCH_NUM);

    return new IteratorFluent($stmt);
}

/**
 * @param \PDOStatement|Statement $stmt
 */
function stmtColumn($stmt, int $columnNumber = 0): IteratorFluent
{
    $generator = function () use ($stmt, $columnNumber) {
        $i = 0;

        while (false !== ($column = $stmt->fetchColumn($columnNumber))) {
            yield $i => $column;

            $i++;
        }
    };

    return new IteratorFluent($generator());
}

/**
 * @param \PDOStatement|Statement $stmt
 */
function stmtAssoc($stmt): IteratorFluent
{
    $stmt->setFetchMode(\PDO::FETCH_ASSOC);

    return new IteratorFluent($stmt);
}

/**
 * @param \PDOStatement|Statement $stmt
 */
function stmtObj($stmt, string $className = \stdClass::class): IteratorFluent
{
    $stmt->setFetchMode(\PDO::FETCH_CLASS, $className);

    return new IteratorFluent($stmt);
}
