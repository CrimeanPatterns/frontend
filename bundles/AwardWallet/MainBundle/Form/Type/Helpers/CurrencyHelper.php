<?php

namespace AwardWallet\MainBundle\Form\Type\Helpers;

use AwardWallet\MainBundle\Entity\Currency;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class CurrencyHelper
{
    public const ALWAYS_CURRENCY_ID = [Currency::MILES_ID, Currency::POINTS_ID];
    public const SORT_PRIORITY_NAME_AFTER_ID = Currency::EURO_ID;

    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    public function getChoices(): array
    {
        $builder = $this->entityManager->getRepository(Currency::class)->createQueryBuilder('c');
        $exp = $builder->expr();
        $builder->where(
            $exp->orX(
                $exp->in('c.currencyid', self::ALWAYS_CURRENCY_ID),
                $exp->isNotNull('c.sign')
            )
        );

        return it($builder->getQuery()->execute())
            ->usort(function (Currency $currency1, Currency $currency2) {
                return [
                    $currency2->getCurrencyid() <= self::SORT_PRIORITY_NAME_AFTER_ID ? $currency1->getCurrencyid() : $currency1->getName(),
                ] <=> [
                    $currency1->getCurrencyid() <= self::SORT_PRIORITY_NAME_AFTER_ID ? $currency2->getCurrencyid() : $currency2->getName(),
                ];
            })
            ->toArray();
    }
}
