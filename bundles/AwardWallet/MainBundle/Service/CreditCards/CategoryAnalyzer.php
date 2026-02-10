<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

class CategoryAnalyzer
{
    /** @var EntityRepository */
    private $repo;
    /** @var ShoppingCategoryGroup[] */
    private $cache = [];

    public function __construct(EntityManagerInterface $em)
    {
        $this->repo = $em->getRepository(ShoppingCategory::class);
    }

    public function analyzeMerchantCategory(array $data): int
    {
        $result = [];

        foreach ($data as $creditCard => $transactions) {
            foreach ($transactions as $transaction) {
                $result[] = array_merge($transaction, ['group' => $this->getCategoryGroup($transaction['category'])]);
            }
        }

        if (empty($result)) {
            return 0;
        }

        usort($result, function (array $a, array $b) {
            $priorityA = $a['group'] ? $a['group']->getPriority() : 0;
            $priorityB = $b['group'] ? $b['group']->getPriority() : 0;

            if ($priorityA === $priorityB) {
                return 0;
            }

            return ($priorityA < $priorityB) ? 1 : -1;
        });

        return $result[0]['category'];
    }

    private function getCategoryGroup(int $category): ?ShoppingCategoryGroup
    {
        if ($category === 0) {
            return null;
        }

        if (!isset($this->cache[$category])) {
            $categoryEntity = $this->repo->find($category);
            $this->cache[$category] = $categoryEntity->getGroup() ?? null;
        }

        return $this->cache[$category];
    }
}
