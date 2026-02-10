<?php

namespace AwardWallet\MainBundle\Service\EnhancedAdmin;

use AwardWallet\MainBundle\Service\EnhancedAdmin\ListConfig\Config;
use Doctrine\ORM\EntityManagerInterface;

class QueryBuilder
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getItems(Config $config): array
    {
        $qb = $this->prepareQueryBuilder($config);

        return $qb->getQuery()->getResult();
    }

    public function getTotals(Config $config): int
    {
        $qb = $this->prepareQueryBuilder($config);

        return $qb->select('COUNT(e)')
            ->setFirstResult(null)
            ->setMaxResults(null)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function prepareQueryBuilder(Config $config): \Doctrine\ORM\QueryBuilder
    {
        $qb = $this->em->createQueryBuilder();
        $sort1 = $config->getSort1();
        $sort1Property = $sort1->getField()->getSortProperty();

        if (strpos($sort1Property, '.') === false) {
            $sort1Property = $config->getAlias() . '.' . $sort1Property;
        }

        $qb->select($config->getAlias())
            ->from($config->getEntity(), $config->getAlias());

        if ($config->getCallbackQueryBuilder()) {
            $config->getCallbackQueryBuilder()($qb);
        }

        $qb
            ->orderBy($sort1Property, $sort1->isAsc() ? 'ASC' : 'DESC')
            ->setFirstResult(($config->getPage() - 1) * $config->getPageSize())
            ->setMaxResults($config->getPageSize());

        return $qb;
    }
}
