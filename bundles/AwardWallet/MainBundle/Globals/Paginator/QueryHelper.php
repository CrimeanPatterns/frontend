<?php

namespace AwardWallet\MainBundle\Globals\Paginator;

use Doctrine\ORM\Query;

/**
 * ORM Query helper for cloning
 * and hint processing.
 */
class QueryHelper
{
    /**
     * Clones the given $query and copies all used
     * parameters and hints.
     *
     * @return Query
     */
    public static function cloneQuery(Query $query)
    {
        $clonedQuery = clone $query;
        $clonedQuery->setParameters($query->getParameters());

        // attach hints
        foreach ($query->getHints() as $name => $hint) {
            $clonedQuery->setHint($name, $hint);
        }

        return $clonedQuery;
    }

    /**
     * Add a custom TreeWalker $walker class name to
     * be included in the CustomTreeWalker hint list
     * of the given $query.
     *
     * @param string $walker
     * @return void
     */
    public static function addCustomTreeWalker(Query $query, $walker)
    {
        $customTreeWalkers = $query->getHint(Query::HINT_CUSTOM_TREE_WALKERS);

        if ($customTreeWalkers !== false && is_array($customTreeWalkers)) {
            $customTreeWalkers = array_merge($customTreeWalkers, [$walker]);
        } else {
            $customTreeWalkers = [$walker];
        }
        $query->setHint(Query::HINT_CUSTOM_TREE_WALKERS, $customTreeWalkers);
    }
}
