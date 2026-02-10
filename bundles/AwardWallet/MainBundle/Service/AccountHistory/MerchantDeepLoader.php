<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class MerchantDeepLoader
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @return Merchant[]
     */
    public function load(array $merchantsIds): array
    {
        $merchants = $this->em->createQueryBuilder()
            ->select('mc, fshc, shc, shcg, fshcg')
            ->from(Merchant::class, 'mc')
            ->leftJoin('mc.forcedShoppingCategory', 'fshc')
            ->leftJoin('mc.shoppingcategory', 'shc')
            ->leftJoin('fshc.group', 'fshcg')
            ->leftJoin('shc.group', 'shcg')
            ->where('mc.id in (:merchantsIds)')
            ->setParameter('merchantsIds', $merchantsIds)
            ->getQuery()
            ->execute();

        $this->em->createQueryBuilder()
            ->select('mcpggm, mcpgg, mcpg, mcpggmcc, mcp, partial mc.{id}')
            ->from(Merchant::class, 'mc')
            ->leftJoin('mc.merchantpattern', 'mcp')
            ->leftJoin('mcp.groups', 'mcpg')
            ->leftJoin('mcpg.merchantgroup', 'mcpgg')
            ->leftJoin('mcpgg.multipliers', 'mcpggm')
            ->leftJoin('mcpggm.creditCard', 'mcpggmcc')
            ->where('mc.id in (:merchantsIds)')
            ->setParameter('merchantsIds', $merchantsIds)
            ->getQuery()
            ->execute();

        $shoppingCategoriesIds =
            it($merchants)
            ->flatMap(function (Merchant $merchant) {
                yield $merchant->getShoppingcategory();

                yield $merchant->getForcedShoppingCategory();
            })
            ->filterNotNull()
            ->map(function (ShoppingCategory $shoppingCategory) { return $shoppingCategory->getId(); })
            ->collect()
            ->unique()
            ->toArray();

        if ($shoppingCategoriesIds) {
            $this->em->createQueryBuilder()
                ->select('shg, shgm, shgmcc, partial sh.{id}')
                ->from(ShoppingCategory::class, 'sh')
                ->leftJoin('sh.group', 'shg')
                ->leftJoin('shg.multipliers', 'shgm')
                ->leftJoin('shgm.creditCard', 'shgmcc')
                ->where('sh.id in (:shoppingCategories)')
                ->setParameter('shoppingCategories', $shoppingCategoriesIds)
                ->getQuery()
                ->execute();
        }

        return $merchants;
    }
}
