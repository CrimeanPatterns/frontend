<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Repositories\SubaccountRepository;
use AwardWallet\MainBundle\Entity\Subaccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use function iter\take;

class SpentAnalysisQueryHandler
{
    private AuthorizationCheckerInterface $authorizationChecker;

    private SpentAnalysisService $spentAnalysisService;

    private SubaccountRepository $subaAccountRep;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        SpentAnalysisService $spentAnalysisService,
        EntityManagerInterface $entityManager
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->spentAnalysisService = $spentAnalysisService;
        $this->subaAccountRep = $entityManager->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
    }

    public function handleRequest(SpentAnalysisQuery $query)
    {
        $this->checkRange($query->getRange());

        if (null !== $query->getMerchant()) {
            if (empty($query->getMerchant())) {
                throw new \InvalidArgumentException('Unavailable \'merchant\' param');
            }

            $this->checkSubAccountAccess($query->getSubAccountIds());

            $result = $this->spentAnalysisService->merchantTransactions(
                $query->getSubAccountIds(),
                $query->getRange(),
                $query->getMerchant(),
                $query->getOfferFilterIds()
            );
            $totals = $this->spentAnalysisService->getTotals($result);
        } else {
            if (empty($query->getSubAccountIds())) {
                throw new \InvalidArgumentException('Empty \'ids\' param');
            }

            $this->checkSubAccountAccess($query->getSubAccountIds());

            $result = $this->spentAnalysisService->merchantsData(
                $query->getSubAccountIds(),
                $query->getRange(),
                $query->getOfferFilterIds(),
                $query->getLimit()
            );
        }

        if ($formatter = $query->getFormatter()) {
            $result = $formatter->format($result, $query, $totals ?? []);
        }

        return $result;
    }

    private function checkRange(?int $range): void
    {
        if (
            !isset($range)
            || empty($range)
            || !$this->spentAnalysisService->validateRange($range)
        ) {
            throw new \InvalidArgumentException('Unavailable \'range\' data');
        }
    }

    private function checkSubAccountAccess(array $subAccountIds): void
    {
        foreach (take(1000, $subAccountIds) as $subAccountId) {
            $subAccount = $this->subaAccountRep->find($subAccountId);

            if (!$subAccount instanceof Subaccount) {
                throw new \InvalidArgumentException('Unavailable SubAccount');
            }

            //            if (!$this->authorizationChecker->isGranted('READ_HISTORY', $subAccount->getAccountid())) {
            //                throw new AccessDeniedException('Access Denied.');
            //            }
        }
    }
}
