<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\DesktopTransactionAnalyzerFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\NextPageToken;
use AwardWallet\MainBundle\Service\AccountHistory\Transaction;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionAnalyzerService;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionQuery;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionQueryCondition;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionTotals;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Twig\Environment;

/**
 * @Route("/transactions")
 */
class TransactionAnalyzerController
{
    private TransactionAnalyzerService $transactionsService;
    private Environment $twig;
    private BankTransactionsAnalyser $bankTransactionsAnalyser;
    private AwTokenStorageInterface $tokenStorage;
    private DesktopTransactionAnalyzerFormatter $formatter;
    private RouterInterface $router;
    private EntityManagerInterface $em;
    private AuthorizationChecker $checker;
    private MileValueService $mileValueService;

    public function __construct(
        TransactionAnalyzerService $transactionsService,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        DesktopTransactionAnalyzerFormatter $formatter,
        AwTokenStorageInterface $tokenStorage,
        Environment $twig,
        RouterInterface $router,
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $checker,
        MileValueService $mileValueService
    ) {
        $this->twig = $twig;
        $this->transactionsService = $transactionsService;
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->formatter = $formatter;
        $this->em = $em;
        $this->checker = $checker;
        $this->mileValueService = $mileValueService;
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/search", name="aw_transactions_business_search", host="%business_host%", options={"expose"=true})
     */
    public function businessSearchAction(): Response
    {
        return new Response($this->twig->render('@AwardWalletMain/Business/SpentAnalysis/search.html.twig', [
            'routeSearchBox' => 'aw_transactions_business_agent',
        ]));
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/{agentId}/list", name="aw_transactions_business_agent", host="%business_host%", options={"expose"=true}, requirements={"agentId":"\d+"})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id"="agentId"})
     */
    public function businessIndexAction(Request $request, Useragent $agent): Response
    {
        $this->checkBusinessAgentGranted($agent);
        $user = $agent->getClientid();

        $context = array_merge(
            $this->generateData($request, $user),
            [
                'userAgentId' => $agent->getId(),
                'routeSearchBox' => 'aw_transactions_business_agent',
                'travelerName' => $user->getFullName(),
            ],
        );

        return new Response($this->twig->render('@AwardWalletMain/Account/Transactions/view.html.twig', $context));
    }

    /**
     * @Route("/", name="aw_transactions_index", options={"expose"=true})
     * @Route("/{subAccountId}", name="aw_transactions_subaccount", options={"expose"=true}, requirements={"subAccountId": "\d+"})
     * @ParamConverter("subAccount", class="AwardWalletMainBundle:Subaccount", options={"id"="subAccountId"})
     * @Security("is_granted('ROLE_USER') && !is_granted('SITE_BUSINESS_AREA')")
     */
    public function indexAction(
        Request $request,
        ?Subaccount $subAccount = null,
        LoggerInterface $logger,
        PageVisitLogger $pageVisitLogger
    ): Response {
        if ($subAccount && !$this->checker->isGranted('READ_EXTPROP', $subAccount->getAccountid())) {
            throw new AccessDeniedException();
        }

        if ($subAccount && $subAccount->getCreditcard() === null) {
            $logger->info('Bad link to transaction analyzer', ['subAccountId' => $subAccount->getSubaccountid()]);

            throw new NotFoundHttpException();
        }

        $user = $this->tokenStorage->getUser();
        $pageVisitLogger->log(PageVisitLogger::PAGE_TRANSACTION_ANALYZER);

        return new Response($this->twig->render('@AwardWalletMain/Account/Transactions/view.html.twig',
            $this->generateData($request, $user, $subAccount)
        ));
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/data", name="aw_transactions_data", options={"expose"=true})
     * @Route("/{agentId}/data", name="aw_transactions_business_data", host="%business_host%", options={"expose"=true})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id"="agentId"})
     */
    public function dataAction(Request $request, ?Useragent $agent = null): Response
    {
        $this->checkBusinessAgentGranted($agent);

        $request->getSession()->save();
        $query = $this->handleRequest($request, $agent);

        if (!$query) {
            return new JsonResponse([]);
        }

        $result = [];
        [$transactions, $newNextPageToken, $isLastPageLoaded] = $this->transactionsService->getTransactions($query);
        $result['transactions'] = $this->formatter->formatTransactions($transactions);
        $result['nextPageToken'] = $newNextPageToken;
        $result['isLastPageLoaded'] = $isLastPageLoaded;
        $result['responseTimeStamp'] = $request->query->getInt('requestTimeStamp');

        return new JsonResponse($result);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/totals", name="aw_transactions_totals", options={"expose"=true})
     * @Route("/{agentId}/totals", name="aw_transactions_business_totals", host="%business_host%", options={"expose"=true})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id"="agentId"})
     */
    public function totalsAction(Request $request, ?Useragent $agent = null): Response
    {
        $this->checkBusinessAgentGranted($agent);

        $request->getSession()->save();
        $query = $this->handleRequest($request, $agent);

        if (!$query) {
            return new JsonResponse([]);
        }

        $response = (array) $this->formatter->formatTotals($this->transactionsService->getTotals($query));
        $response['responseTimeStamp'] = $request->query->getInt('requestTimeStamp');

        return new JsonResponse($response);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/export.csv", name="aw_transactions_export_csv", options={"expose"=true})
     * @Route("/{agentId}/export.csv", name="aw_transactions_business_export_csv", options={"expose"=true})
     * @ParamConverter("agent", class="AwardWalletMainBundle:Useragent", options={"id"="agentId"})
     */
    public function exportCsvAction(
        Request $request,
        LocalizeService $localizeService,
        ?Useragent $agent
    ): Response {
        $this->checkBusinessAgentGranted($agent);
        $query = $this->handleRequest($request, $agent);

        if (!$query) {
            return new JsonResponse([]);
        }
        $query->setLimit(null);

        [$transactions] = $this->transactionsService->getTransactions($query);
        /** @var TransactionTotals $totals */
        $totals = $this->formatter->formatTotals(
            $this->transactionsService->getTotals($query)
        );
        $transactions = $this->formatter->formatTransactions($transactions);

        $content = '"' . implode('","', [
            'Date', 'Description', 'CreditCard', 'Category', 'Amount', 'Points',
            'Cash Equivalent', 'Potential Points', 'Potential Cash Equivalent',
        ]) . '"';

        $totalSum = ['potentialMiles' => 0, 'potentialCashEquivalent' => 0];
        array_walk($transactions, static function (Transaction $row) use (&$content, &$totalSum, $localizeService) {
            $isLike = $row->pointsValue > $row->potentialPointsValue;
            $isPositive = $row->amount > 0;
            $potentialMiles = $potentialPointsValue = '';

            if (!$isLike && $isPositive) {
                $potentialMiles = $row->potentialMiles;
                $totalSum['potentialMiles'] += $potentialMiles;
                $potentialPointsValue = $row->potentialPointsValue;
                $totalSum['potentialCashEquivalent'] += $potentialPointsValue;
            }

            $content .= "\n" . sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s"',
                $row->dateFormatted ?? $row->formatted['date'],
                $row->description,
                $row->cardName,
                $row->category,
                $row->amountFormatted ?? $row->formatted['amount'],
                $row->milesFormatted ?? $row->formatted['miles'],
                $row->pointsValueFormatted ?? $row->formatted['pointsValue'],
                empty($potentialMiles) ? '' : $localizeService->formatNumber($potentialMiles),
                empty($potentialPointsValue) ? '' : $localizeService->formatCurrency($potentialPointsValue, 'USD')
            );
        });

        $content .= "\n" . sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s"', '', 'Total', '', '',
            $totals->amountFormatted, $totals->milesFormatted, $totals->pointsValueFormatted,
            $localizeService->formatNumber($totalSum['potentialMiles']),
            $localizeService->formatCurrency($totalSum['potentialCashEquivalent'], 'USD')
        );

        $response = new Response($content);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export.csv"');

        return $response;
    }

    private function checkBusinessAgentGranted(?Useragent $agent): void
    {
        if (null === $agent) {
            return;
        }

        if (!$this->checker->isGranted('VIEW_SPEND_ANALYSIS', $agent)) {
            throw new AccessDeniedException();
        }
    }

    private function generateData(
        Request $request,
        Usr $user,
        ?Subaccount $subAccount = null
    ): array {
        $spentAnalysisInitial = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($user, true);

        $selectedUser = null;
        $owners = [];

        foreach ($spentAnalysisInitial['ownersList'] as $key => $owner) {
            $owners[] = [
                'id' => $key,
                'name' => $owner['name'],
                'cards' => $owner['availableCards'],
                'haveCardsId' => array_values(array_unique($owner['haveCardsId'])),
            ];

            if (!$selectedUser) {
                $selectedUser = $key;
            }

            // select a family member with cards, if there are no cards on default user
            if ($selectedUser
                && count($spentAnalysisInitial['ownersList'][$selectedUser]['availableCards']) === 0
                && $owner['availableCards'] > 0
            ) {
                $selectedUser = $key;
            }
        }

        $defaultRange = BankTransactionsDateUtils::THIS_YEAR;
        $selectedCard = null;

        if (isset($spentAnalysisInitial['ownersList'][$selectedUser])) {
            if (!$subAccount) {
                $ids = $this->getOwnerCards($spentAnalysisInitial['ownersList'][$selectedUser]);
            } else {
                $selectedCard = $subAccount->getSubaccountid();
                $ids = [$selectedCard];
            }

            $initialOfferCardsCount = array_sum(array_map(static fn ($list) => count($list['cardsList']), $spentAnalysisInitial['offerCardsFilter']));
            $offerCards = empty($offerCards = $request->cookies->get(TransactionAnalyzerService::COOKIE_OFFER_CARDS_KEY, ''))
                ? null
                : array_map('intval', explode('.', $offerCards));

            if (empty($offerCards)) {
                $offerCards = [];

                foreach ($spentAnalysisInitial['offerCardsFilter'] as $provider) {
                    foreach ($provider['cardsList'] as $card) {
                        $offerCards[] = $card['creditCardId'];
                    }
                }
            }

            if (null === $offerCards || count($offerCards) === $initialOfferCardsCount) {
                $offerCards = [];
            }

            $range = BankTransactionsDateUtils::findRangeLimits($defaultRange);
            $query = (new TransactionQuery($ids))
                ->setOfferCards($offerCards)
                ->setRangeLimits(
                    new \DateTime($range['start']),
                    new \DateTime($range['end'])
                );

            [$transactions, $nextPageToken, $isLastPageLoaded] = $this->transactionsService->getTransactions($query);

            /* select last year range if this year is empty */
            if (empty($transactions)) {
                $defaultRange = BankTransactionsDateUtils::LAST_YEAR;
                $lastYear = BankTransactionsDateUtils::findRangeLimits($defaultRange);
                $query->setRangeLimits(
                    new \DateTime($lastYear['start']),
                    new \DateTime($lastYear['end'])
                );
                [$transactions, $nextPageToken, $isLastPageLoaded] = $this->transactionsService->getTransactions($query);
            }

            $rows = $this->formatter->formatTransactions($transactions);
        }

        $existed = [];

        foreach ($spentAnalysisInitial['accounts'] as $item) {
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

        $transactions = $rows ?? [];

        return [
            'awFree' => !$user->isAwPlus(),

            'selectedUser' => $selectedUser,
            'selectedCard' => $selectedCard,

            'exportUrl' => $this->router->generate('aw_transactions_export_csv'),
            'transactions' => $transactions,
            'nextPageToken' => $nextPageToken ?? null,
            'isLastPageLoaded' => $isLastPageLoaded ?? true,
            'offerCardsFilter' => $spentAnalysisInitial['offerCardsFilter'],
            'offerCardsState' => $offerCards ?? [],
            'owners' => $owners,
            'defaultRange' => $defaultRange,

            'noTransactions' => empty($transactions),
            'noAccounts' => empty($existed),
            'providers' => $providersSorted,
            'user' => [
                'mileValue' => $this->mileValueService->getBankPointsShortData(true, true),
            ],
        ];
    }

    private function getOwnerCards($ownerData)
    {
        $ids = [];

        if ($ownerData['availableCards'] && count($ownerData['availableCards'])) {
            $ids = array_map(function ($cardItem) {
                return $cardItem['subAccountId'];
            }, $ownerData['availableCards']);
        }

        return $ids;
    }

    private function handleRequest(Request $request, ?Useragent $userAgent = null): ?TransactionQuery
    {
        $params = array_merge($request->request->all(), $request->query->all());
        $nextPageToken = !empty($params['nextPage']) ? NextPageToken::createFromString($params['nextPage']) : null;
        $descriptionFilter = $params['descriptionFilter'] ?? null;
        $offerFilterIds = $params['offerFilterIds'] ?? null;
        $subAccIds = $params['subAccIds'] ?? null;
        $datesRange = $params['datesRange'] ?? null;
        $startDate = $params['startDate'] ?? null;
        $endDate = $params['endDate'] ?? null;
        $agent = $params['agent'] ?? null;
        $categories = $params['categories'] ?? null;
        $amountLess = $params['amountLess'] ?? null;
        $amountGreater = $params['amountGreater'] ?? null;
        $amountExactly = $params['amountExactly'] ?? null;
        $multiplier = $params['multipliers'] ?? null;
        $potential = $params['potential'] ?? null;
        $withPotential = (isset($params['withPotential']) && $params['withPotential'] === 'false') ? false : true;

        if (null !== $userAgent) {
            $this->checkBusinessAgentGranted($userAgent);
            $user = $userAgent->getClientid();
        } else {
            $user = $this->tokenStorage->getUser();
        }
        $spentAnalysisInitial = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($user, true);

        if (!isset($spentAnalysisInitial['ownersList'][$agent])) {
            return null;
        }

        $ids = $this->getOwnerCards($spentAnalysisInitial['ownersList'][$agent]);

        /* filter by credit cards */
        if ($subAccIds) {
            $ids = $subAccIds;
        }

        $query = (new TransactionQuery($ids, $descriptionFilter, $nextPageToken))
            ->setOfferCards($offerFilterIds)
            ->setWithEarningPotential($withPotential);

        /* categories */
        if ($categories) {
            $query->setCategories($categories);
        }
        /* categories end */

        /* dates range */
        if ((int) $datesRange > 0) {
            $range = BankTransactionsDateUtils::findRangeLimits((int) $datesRange);
            $query->setRangeLimits(
                new \DateTime($range['start']),
                new \DateTime($range['end'])
            );
        }

        if ($startDate && $endDate && !$query->getStartDate()) {
            $query->setRangeLimits(
                new \DateTime($startDate),
                new \DateTime($endDate)
            );
        }
        /* dates range end */

        /* amount conditions */
        if ((float) $amountLess > 0 || (float) $amountGreater > 0 || (float) $amountExactly > 0) {
            $query->setAmountCondition(new TransactionQueryCondition((float) $amountLess, (float) $amountGreater, (float) $amountExactly));
        }
        /* amount conditions end */

        if (is_array($multiplier)) {
            $query->setPointsMultiplier(array_map(
                function ($item) {
                    return round((float) $item, 1);
                },
                $multiplier
            ));
        }

        if (is_array($potential)) {
            $query->setEarningPotentialMultiplier(array_map(
                function ($item) {
                    return round((float) $item, 1);
                },
                $potential
            ));
        }

        return $query;
    }
}
