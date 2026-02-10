<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\BusinessTransaction\MembershipRenewed;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPeriod;
use AwardWallet\MainBundle\Globals\Cart\SubscriptionPrice;
use AwardWallet\MainBundle\Globals\Paginator\Paginator;
use AwardWallet\MainBundle\Service\BusinessTransaction\BusinessTransactionManager;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

class BalanceController extends AbstractController
{
    public const perPage = 20;

    private AwTokenStorageInterface $tokenStorage;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/balance", name="aw_business_balance", options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Balance/balance.html.twig")
     */
    public function balanceAction(Request $request, BusinessTransactionManager $transactionManager, Paginator $paginator)
    {
        $business = $this->tokenStorage->getBusinessUser();
        $transactionRep = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\BusinessTransaction::class);

        $membersCount = $transactionManager->getMembersCount($business);
        $membersPrice = MembershipRenewed::AMOUNT;
        $membersCost = $membersCount * $membersPrice;
        $membersDiscount = $business->getBusinessInfo()->getDiscount();
        $membersDiscount = $membersDiscount > 100 ? 100 : $membersDiscount;
        $membersCostDiscounted = $membersCost * (100 - $membersDiscount) / 100;

        $plusCount = $transactionManager->getKeepUpgradedMembersCount($business);
        $plusPrice = SubscriptionPrice::getPrice(Usr::SUBSCRIPTION_TYPE_AWPLUS, SubscriptionPeriod::DURATION_1_YEAR);
        $plusCost = $plusCount * $plusPrice / 12;

        $abRequestCount = 0;
        $abRequestPrice = 20;
        $abRequestCost = 0;
        $abRequestDiscount = 0;
        $abRequestCostDiscounted = 0;

        if ($business->isBooker()) {
            $abRequestCount = $transactionManager->getClosedRequestsCount($business);
            $abRequestCost = $abRequestCount * $abRequestPrice;
            $abRequestDiscount = $business->getBookerInfo()->getDiscount();
            $abRequestDiscount = $abRequestDiscount > 100 ? 100 : $abRequestDiscount;
            $abRequestCostDiscounted = $abRequestCost * (100 - $abRequestDiscount) / 100;
        }

        $recommendedMonthlyPayment = ceil(($membersCostDiscounted + $plusCost + $abRequestCostDiscounted) / 10) * 10;

        $query = $transactionRep->getTransactionQuery($business);

        $pagination = $paginator->paginate($query, $request->query->get('page', 1), self::perPage);
        $pagination->setTemplate('@AwardWalletMain/Business/Balance/pagination.html.twig');

        // estimated
        $total = $membersCostDiscounted + $plusCost + $abRequestCostDiscounted;
        $balance = $business->getBusinessInfo()->getBalance();
        $estimated = -1;

        if ($balance > 0 && $total > 0) {
            $estimated = floor($balance / $total);
        } elseif ($total == 0) {
            $estimated = 13;
        }

        if ($business->getBusinessInfo()->isTrial()) {
            $interval = $business->getBusinessInfo()->getTrialEndDate()->diff(new \DateTime());

            if ($interval->m > $estimated) {
                $estimated = $interval->m;
            }
        } elseif (!$business->getBusinessInfo()->isPaid()) {
            $estimated--;
        }

        return [
            'business' => $business,
            'membersCount' => $membersCount,
            'membersPrice' => $membersPrice,
            'membersCost' => $membersCost,
            'membersDiscount' => $membersDiscount,
            'membersCostDiscounted' => $membersCostDiscounted,
            'plusCount' => $plusCount,
            'plusPrice' => $plusPrice,
            'plusCost' => $plusCost,
            'abRequestCount' => $abRequestCount,
            'abRequestPrice' => $abRequestPrice,
            'abRequestCost' => $abRequestCost,
            'abRequestDiscount' => $abRequestDiscount,
            'abRequestCostDiscounted' => $abRequestCostDiscounted,
            'recommendedMonthlyPayment' => $recommendedMonthlyPayment,
            'pagination' => $pagination,
            'estimated' => intval($estimated),
        ];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS') and is_granted('ROLE_STAFF') and is_granted('SITE_DEV_MODE')")
     * @Route("/balance/normal", name="aw_business_test_normal_payment", options={"expose"=true})
     */
    public function normalPaymentAction(BusinessTransactionManager $manager)
    {
        $this->tokenStorage->getBusinessUser()->getBusinessInfo()->setPaidUntilDate(null);
        $manager->billMonth($this->tokenStorage->getBusinessUser(), null);

        return $this->redirect($this->router->generate('aw_business_balance'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS') and is_granted('ROLE_STAFF') and is_granted('SITE_DEV_MODE')")
     * @Route("/balance/partial", name="aw_business_test_partial_payment", options={"expose"=true})
     */
    public function partialPaymentAction(BusinessTransactionManager $manager)
    {
        $this->tokenStorage->getBusinessUser()->getBusinessInfo()->setPaidUntilDate(null);
        $manager->billMonth($this->tokenStorage->getBusinessUser(), (new \DateTime('@' . strtotime("last day of")))->add(\DateInterval::createFromDateString('-5 days')));

        return $this->redirect($this->router->generate('aw_business_balance'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS') and is_granted('ROLE_STAFF') and is_granted('SITE_DEV_MODE')")
     * @Route("/balance/balance", name="aw_business_test_set_balance", options={"expose"=true})
     */
    public function setBalanceAction(Request $request)
    {
        $this->tokenStorage->getBusinessUser()->getBusinessInfo()->setBalance($request->get('balance'));
        $this->entityManager->flush();

        return $this->redirect($this->router->generate('aw_business_balance'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS') and is_granted('ROLE_STAFF') and is_granted('SITE_DEV_MODE')")
     * @Route("/balance/payment", name="aw_business_test_add_payment", options={"expose"=true})
     */
    public function addPaymentAction(Request $request, BusinessTransactionManager $manager)
    {
        $manager->getBusiness()->getBusinessInfo()->setPaidUntilDate(null);
        $manager->addPayment($manager->getBusiness(), $request->get('amount'));

        return $this->redirect($this->router->generate('aw_business_balance'));
    }
}
