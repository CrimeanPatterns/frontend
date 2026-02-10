<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\CreditCardShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\lazy;

class AnalyserContextFactory
{
    private MileValueService $mileValueService;
    private EntityManagerInterface $em;
    private ParameterRepository $parameterRepository;

    public function __construct(
        MileValueService $mileValueService,
        EntityManagerInterface $em,
        ParameterRepository $parameterRepository
    ) {
        $this->mileValueService = $mileValueService;
        $this->em = $em;
        $this->parameterRepository = $parameterRepository;
    }

    public function makeCacheContext(): Context
    {
        return new Context(
            $mileValueData = lazy(function () {
                return $this->mileValueService->getData();
            }),
            lazy(function () use ($mileValueData) {
                $result = [];

                foreach ($mileValueData() as $type => $items) {
                    foreach ($items['data'] as $providerId => $providerData) {
                        if (!isset($result[$providerId])) {
                            $result[$providerId] = \array_merge(
                                $providerData,
                                ['group' => $type]
                            );
                        }
                    }
                }

                return $result;
            }),
            lazy(function () {
                return $this->mileValueService->getFlatDataListById();
            }),
            lazy(function () {
                return
                    $this->em
                        ->createQueryBuilder()
                        ->select('ccscg, cc')
                        ->from(CreditCardShoppingCategoryGroup::class, 'ccscg')
                        ->join('ccscg.creditCard', 'cc')
                        ->where('ccscg.shoppingCategoryGroup is null')
                        ->getQuery()
                        ->execute();
            }),
            lazy(function () {
                return
                    it(
                        $this->em
                            ->createQueryBuilder()
                            ->select('cc, ccm')
                            ->from(CreditCard::class, 'cc')
                            ->leftJoin('cc.multipliers', 'ccm')
                            ->getQuery()
                            ->execute()
                    )
                        ->reindex(function (CreditCard $card) {
                            return $card->getId();
                        })
                        ->toArrayWithKeys();
            }),
            lazy(fn () => (int) $this->parameterRepository->getParam(ParameterRepository::MERCHANT_REPORT_VERSION)),
            null,
            lazy(function () {
                $cards = $this->em->getConnection()->fetchAllAssociative('
                    SELECT CreditCardID, ProviderID, CobrandProviderID, IsCashBackOnly, CashBackType
                    FROM CreditCard
                ');

                return array_column($cards, null, 'CreditCardID');
            }),
        );
    }
}
