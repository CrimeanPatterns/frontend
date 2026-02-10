<?php

namespace AwardWallet\MainBundle\Controller\Business;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

/**
 * @Route()
 */
class SpentAnalysisController extends AbstractController
{
    private BankTransactionsAnalyser $bankTransactionsAnalyser;
    private AuthorizationChecker $checker;
    private Environment $twig;
    private EntityManagerInterface $em;
    private MileValueService $mileValueService;

    public function __construct(
        AuthorizationCheckerInterface $checker,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        Environment $twig,
        EntityManagerInterface $em,
        MileValueService $mileValueService
    ) {
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->checker = $checker;
        $this->twig = $twig;
        $this->em = $em;
        $this->mileValueService = $mileValueService;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/spend-analysis/search", name="aw_spent_analysis_business_search", options={"expose"=true})
     */
    public function searchAction()
    {
        return new Response($this->twig->render('@AwardWalletMain/Business/SpentAnalysis/search.html.twig'));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/spend-analysis/{agentId}", name="aw_spent_analysis_business", options={"expose"=true}, requirements={"agentId": "\d+"})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id" = "agentId"})
     */
    public function indexAction(Request $request, Useragent $agent)
    {
        if (!$this->checker->isGranted('VIEW_SPEND_ANALYSIS', $agent)) {
            throw new AccessDeniedException();
        }

        $analysisData = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($agent->getAgentid());

        $criteria = [
            'providerid' => Provider::EARNING_POTENTIAL_LIST,
        ];

        if ($agent->getClientid()) {
            $criteria['user'] = $agent->getClientid();
        } else {
            $criteria['user'] = $agent->getAgentid();
            $criteria['userAgent'] = $agent;
        }

        $lastSuccessCheckAccount = $this->em->getRepository(Account::class)
            ->findOneBy($criteria, ['successcheckdate' => 'DESC']);

        $lastSuccessCheck = null;
        $onlyOldTransactions = false;

        if ($lastSuccessCheckAccount) {
            $lastSuccessCheck = $lastSuccessCheckAccount->getSuccesscheckdate();
            $oldestDate = new \DateTime(
                BankTransactionsDateUtils::findRangeLimits(BankTransactionsDateUtils::LAST_QUARTER)['start']
            );
            $onlyOldTransactions = $lastSuccessCheck < $oldestDate;
        }

        $existed = [];

        foreach ($analysisData['accounts'] as $item) {
            if (!isset($existed[$item['providerCode']])) {
                $existed[$item['providerCode']] = 0;
            }
            $existed[$item['providerCode']]++;
        }

        $providers = $this->em->getRepository(Provider::class)->findBy([
            'providerid' => Provider::EARNING_POTENTIAL_LIST,
        ]);
        $providersSorted = [];

        /** @var Provider $provider */
        foreach ($providers as $provider) {
            $newIndex = array_search($provider->getProviderid(), Provider::EARNING_POTENTIAL_LIST);
            $providersSorted[$newIndex] = [
                'id' => $provider->getProviderid(),
                'displayName' => $provider->getDisplayname(),
                'accountsCounter' => $existed[$provider->getCode()] ?? 0,
            ];
        }
        ksort($providersSorted);

        return new Response($this->twig->render('@AwardWalletMain/SpentAnalysis/index.html.twig', [
            'user' => [
                'isAwPlus' => true,
                'mileValue' => $this->mileValueService->getBankPointsShortData(true),
                'selectedAgent' => $agent->getId(),
                'name' => $agent->getFullName(),
            ],
            'providers' => $providersSorted,
            'cards' => $analysisData,
            'data' => [
                'lastSuccessCheck' => $lastSuccessCheckAccount instanceof Account ?
                    $lastSuccessCheckAccount->getSuccesscheckdate()->getTimestamp() : null,
                'onlyOldTransactions' => $onlyOldTransactions,
            ],
        ]));
    }
}
