<?php

namespace AwardWallet\MainBundle\Globals\Paginator;

use Doctrine\ORM\Query\SqlWalker;

class MysqlCountTreeWalker extends SqlWalker
{
    /**
     * Walks down a SelectClause AST node, thereby generating the appropriate SQL.
     *
     * @return string the SQL
     */
    public function walkSelectClause($selectClause)
    {
        $sql = parent::walkSelectClause($selectClause);

        if ($selectClause->isDistinct) {
            $sql = str_replace('SELECT DISTINCT', 'SELECT DISTINCT SQL_CALC_FOUND_ROWS', $sql);
        } else {
            $sql = str_replace('SELECT', 'SELECT SQL_CALC_FOUND_ROWS', $sql);
        }

        return $sql;
    }
}
