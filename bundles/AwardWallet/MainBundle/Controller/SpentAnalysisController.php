<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\AccountHistory;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Security\Captcha\Resolver\DesktopCaptchaResolver;
use AwardWallet\MainBundle\Service\AccountHistory\Async\TransactionsExistTask;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\DesktopSpentAnalysisFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\OfferQuery;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQuery;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQueryHandler;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\AccountHistory\Transaction;
use AwardWallet\MainBundle\Service\MerchantLookupHandler;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\SocksMessaging\Client;
use AwardWallet\MainBundle\Service\SocksMessaging\UserMessaging;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class SpentAnalysisController extends BaseController
{
    public const DEFAULT_CURRENCY = 'USD';

    /** @var LoggerInterface */
    private $logger;

    /** @var SpentAnalysisService */
    private $analysisService;

    /** @var EntityManagerInterface */
    private $em;

    /** @var AuthorizationChecker */
    private $checker;

    /** @var LocalizeService */
    private $localizer;

    /** @var SpentAnalysisQueryHandler */
    private $spentAnalysisRequestHandler;

    /** @var DesktopSpentAnalysisFormatter */
    private $analysisFormatter;

    /** @var BankTransactionsAnalyser */
    private $bankTransactionAnalyser;

    /** @var Router */
    private $router;

    /** @var AwTokenStorage */
    private $tokenStorage;

    /** @var MerchantLookupHandler */
    private $merchantLookupHandler;

    /** @var MileValueService */
    private $mileValueService;
    private DesktopCaptchaResolver $captchaResolver;
    private MileValueCards $mileValueCards;

    public function __construct(
        LoggerInterface $logger,
        SpentAnalysisService $analysisService,
        EntityManagerInterface $em,
        AuthorizationChecker $checker,
        LocalizeService $localizer,
        SpentAnalysisQueryHandler $spentAnalysisRequestHandler,
        DesktopSpentAnalysisFormatter $analysisFormatter,
        BankTransactionsAnalyser $bankTransactionAnalyser,
        Router $router,
        AwTokenStorage $tokenStorage,
        MerchantLookupHandler $merchantLookupHandler,
        DesktopCaptchaResolver $captchaResolver,
        MileValueService $mileValueService,
        MileValueCards $mileValueCards
    ) {
        $this->logger = $logger;
        $this->analysisService = $analysisService;
        $this->em = $em;
        $this->checker = $checker;
        $this->localizer = $localizer;
        $this->spentAnalysisRequestHandler = $spentAnalysisRequestHandler;
        $this->analysisFormatter = $analysisFormatter;
        $this->bankTransactionAnalyser = $bankTransactionAnalyser;
        $this->router = $router;
        $this->tokenStorage = $tokenStorage;
        $this->merchantLookupHandler = $merchantLookupHandler;
        $this->mileValueService = $mileValueService;
        $this->captchaResolver = $captchaResolver;
        $this->mileValueCards = $mileValueCards;
    }

    /**
     * @Route("/spend-analysis", name="aw_spent_analysis_index", options={"expose"=true})
     * @Security("is_granted('ROLE_USER')")
     * @Template("@AwardWalletMain/SpentAnalysis/index.html.twig")
     * @return mixed|RedirectResponse
     */
    public function indexAction(Request $request, PageVisitLogger $pageVisitLogger)
    {
        if ($this->checker->isGranted('SITE_BUSINESS_AREA')) {
            return new RedirectResponse($this->router->generate('aw_spent_analysis_business_search'));
        }

        $providers = $this->em->getRepository(Provider::class)->findBy([
            'providerid' => Provider::EARNING_POTENTIAL_LIST,
        ]);

        $analysisData = $this->bankTransactionAnalyser->getSpentAnalysisInitial(null, true);
        $onlyOldTransactions = false;

        if ($lastSuccessCheck = $this->getEarningPotentialLastSuccessCheckDate()) {
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
        $providersSorted = [];

        /** @var Provider $provider */
        foreach ($providers as $provider) {
            $newIndex = array_search($provider->getId(), Provider::EARNING_POTENTIAL_LIST);
            $providersSorted[$newIndex] = [
                'id' => $provider->getId(),
                'displayName' => $provider->getDisplayname(),
                'accountsCounter' => $existed[$provider->getCode()] ?? 0,
            ];
        }
        ksort($providersSorted);
        $pageVisitLogger->log(PageVisitLogger::PAGE_CREDIT_CARD_SPEND_ANALYSIS);

        return [
            'user' => [
                /* enable for all users */
                'mileValue' => $this->mileValueService->getBankPointsShortData(true, true),
            ],
            'providers' => $providersSorted,
            'cards' => $analysisData,
            'data' => [
                'lastSuccessCheck' => $lastSuccessCheck ? $lastSuccessCheck->getTimestamp() : null,
                'onlyOldTransactions' => $onlyOldTransactions,
            ],
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/spend-analysis/transactions-exists",
     *     name="aw_spent_analysis_transactions_exists",
     *     options={"expose"=true},
     *     methods={"POST"}
     * )
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function transactionsExistsAction(Request $request, Process $asyncProcess, Client $messaging)
    {
        $postData = [
            'ids' => $request->request->get('ids'),
        ];

        if (!isset($postData['ids']) || !is_array($postData['ids']) || empty($postData['ids'])) {
            throw new BadRequestHttpException('Unavailable \'ids\' param');
        }

        foreach ($postData['ids'] as $id) {
            $this->checkSubAccountId((int) $id);
        }

        $channel = UserMessaging::getChannelName('transactionsexist' . bin2hex(random_bytes(3)), $this->tokenStorage->getUser()->getUserid());
        $task = new TransactionsExistTask($postData['ids'], $channel);
        // 3 delaySeconds - https://redmine.awardwallet.com/issues/20611
        // does not have time to subscribe before sending the task in spentAnalysis/main.js:awaitCentrifugeData()
        $asyncProcess->execute($task, 3);
        //        $result = $this->analysisService->transactionsExists($postData['ids']);

        return new JsonResponse([
            //            'transactionsInfo' => $result,
            "channel" => $channel,
            'centrifuge_config' => $messaging->getClientData(),
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/spend-analysis/merchants/transactions",
     *     name="aw_spent_analysis_merchants_transactions",
     *     options={"expose"=true},
     *     methods={"POST"}
     * )
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function merchantTransactionsAction(Request $request)
    {
        try {
            return new JsonResponse($this->spentAnalysisRequestHandler->handleRequest(
                SpentAnalysisQuery::createMerchantTransactionQueryByRequest($request)
                    ->setFormatter($this->analysisFormatter)
            ));
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/spend-analysis/merchants/data",
     *     name="aw_spent_analysis_merchants_data",
     *     options={"expose"=true},
     *     methods={"POST"}
     * )
     * @return JsonResponse
     * @throws \Doctrine\DBAL\DBALException
     */
    public function merchantsDataAction(Request $request)
    {
        try {
            return new JsonResponse($this->spentAnalysisRequestHandler->handleRequest(
                SpentAnalysisQuery::createMerchantDataQueryByRequest($request)
                    ->setFormatter($this->analysisFormatter)
            ));
        } catch (AccessDeniedException $e) {
            throw new AccessDeniedHttpException($e->getMessage());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/spend-analysis/transaction-offer",
     *     name="aw_spent_analysis_transaction_offer",
     *     options={"expose"=true},
     *     methods={"POST"}
     * )
     * @Template("@AwardWalletMain/SpentAnalysis/offer.html.twig")
     * @return array
     */
    public function transactionOfferAction(Request $request)
    {
        return $this->getTransactionOfferData($request);
    }

    public function getTransactionOfferData(Request $request): array
    {
        $session = $request->getSession();

        if ($session) {
            $session->save();
        }

        $postData = [
            'source' => $request->request->get('source'),
            'uuid' => $request->request->get('uuid'),
            'offerFilterIds' => $request->request->get('offerFilterIds', []),
            'amount' => $request->request->get('amount'),
            'miles' => $request->request->get('miles'),
        ];

        $this->logger->info('offer post data', ['offer_post_data' => $postData]);

        if (empty($postData['uuid']) || !is_scalar($postData['uuid'])) {
            throw new BadRequestHttpException('Unavailable "uuid" param');
        }

        /** @var AccountHistory $historyRow */
        $historyRow = $this->em->getRepository(AccountHistory::class)->find($postData['uuid']);

        if (!$historyRow instanceof AccountHistory) {
            throw new BadRequestHttpException('Unavailable history row. uuid=' . $postData['uuid']);
        }

        //        if (!$this->checker->isGranted('READ_HISTORY', $historyRow->getAccount())) {
        //            throw new AccessDeniedHttpException('Access Denied.');
        //        }
        if (!empty($postData['amount']) && !empty($postData['miles'])) {
            $historyRow
                ->setAmount((float) $postData['amount'])
                ->setMiles((float) $postData['miles']);
        }

        $offerData = $this->analysisService->buildOffer(
            OfferQuery::createFromHistoryRow(
                $historyRow,
                $postData['source'],
                $postData['offerFilterIds'],
                $this->mileValueCards
            )
        );

        if (!$offerData) {
            throw new BadRequestHttpException('No cards to offer');
        }

        $offerData['historyRow'] = $historyRow;
        $offerData['filterIds'] = $postData['offerFilterIds'];
        $offerData['amount'] = $this->localizer->formatCurrency($offerData['amount'], self::DEFAULT_CURRENCY, false);
        $offerData['miles'] = $this->localizer->formatNumber($offerData['miles']);
        $offerData['multiplier'] = $this->localizer->formatNumber($offerData['multiplier']);
        $offerData['isAuth'] = $this->checker->isGranted('ROLE_USER');

        return $offerData;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/spend-analysis/transaction-cards-offer", name="aw_spent_analysis_transaction_cards_offer", options={"expose"=true}, methods={"POST"})
     */
    public function bestCardsWithOffers(
        Request $request,
        SpentAnalysisService $spentAnalysisService
    ): JsonResponse {
        $offerData = $this->getTransactionOfferData($request);
        $offersHtml = $this->render("@AwardWalletMain/SpentAnalysis/offer.html.twig", $offerData)->getContent();
        $result = $spentAnalysisService->fetchTransactionOfferWithBestCard($offerData);
        $result['offersHtml'] = $offersHtml;

        return new JsonResponse($result);
    }

    /**
     * @Route("/spend-analysis/merchant-offer-by-name/{merchantName}",
     *     name="aw_merchant_by_name_lookup_offer",
     *     requirements={"merchantName"=".+"},
     *     options={"expose"=true},
     *     methods={"GET"}
     * )
     * @Template("@AwardWalletMain/SpentAnalysis/offer.html.twig")
     * @return array
     */
    public function merchantByNameOfferAction(Request $request, string $merchantName)
    {
        $isAuth = $this->checker->isGranted('ROLE_USER');

        if (!$isAuth) {
            if (!$this->captchaResolver->resolve($request)->getValidator()->validate($request->headers->get("recaptcha") ?? '', $request->getClientIp() ?? '')) {
                throw new BadRequestHttpException('Invalid captcha');
            }
        }

        $session = $request->getSession();

        if ($session) {
            $session->save();
        }

        $result = $this->merchantLookupHandler->handleExactMatchRequest($request, $merchantName, OfferQuery::SOURCE_WEB_MCC);

        if (null === $result) {
            throw new BadRequestHttpException('Invalid merchant');
        }

        if (null !== $request->get('showPercent')) {
            $result['showPercent'] = true;
        }
        $result['isAuth'] = $isAuth;

        return $result;
    }

    /**
     * @Route("/spend-analysis/chart/{device}", name="aw_spent_analysis_chart", requirements={"device":"desktop|mobile"})
     * @return Response
     */
    public function chartAction(Request $request, string $device)
    {
        $isDesktop = 'desktop' === $device;
        [$graphWidth, $barWidth, $maxBars, $labelMaxLength, $fontSize] = $isDesktop
            ? [800, 38, 6, 16, 9]
            : [300, 18, 4, 7, 7];

        $datas = [
            'earned' => $request->query->get('e'),
            'potential' => $request->query->get('p'),
            'label' => $request->query->get('l'),
        ];

        foreach ($datas as $key => $data) {
            $datas[$key] = is_string($data) ? explode('_', $data) : null;

            if ('label' === $key) {
                foreach ($datas[$key] as &$label) {
                    if (strlen($label) > $labelMaxLength) {
                        $label = implode("\n", StringHandler::splitWithoutBreakWord($label, $labelMaxLength));
                    }
                }
            } else {
                $datas[$key] = array_map('intval', $datas[$key]);
            }
        }

        if (empty($datas['earned'])
            || \count($datas['earned']) !== \count($datas['potential'])
            || \count($datas['earned']) !== \count($datas['label'])) {
            throw new BadRequestHttpException('Incorrect data');
        }

        // transfer "Other" to the end graph
        if (\count($datas['earned']) > $maxBars - 1) {
            [$otherEarned, $otherPotential, $otherLabel] = [
                array_pop($datas['earned']),
                array_pop($datas['potential']),
                array_pop($datas['label']),
            ];

            foreach ($datas as $key => $data) {
                $datas[$key] = array_slice($data, 0, $maxBars - 1);
            }
            $datas['earned'][] = $otherEarned;
            $datas['potential'][] = $otherPotential;
            $datas['label'][] = $otherLabel;
        }

        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('bar');

        foreach ($datas as $key => $data) {
            $datas[$key] = array_splice($data, 0, $maxBars);
        }

        $graph = new \Graph($graphWidth, 360, 'auto');
        $graph->SetScale('textlin');

        $graph->SetTheme(new \SoftyTheme());
        $graph->SetBox(false);
        $graph->ygrid->SetFill(false);
        $graph->xaxis->SetTickLabels($datas['label']);
        $graph->xaxis->SetColor('#8f8f8f');
        $graph->xaxis->font_size = $fontSize;
        $graph->yaxis->SetLabelFormatCallback(function ($label) {
            return $this->localizer->formatNumber($label);
        });

        $b1plot = new \BarPlot($datas['earned']);
        $b2plot = new \BarPlot($datas['potential']);

        $gbplot = new \GroupBarPlot([$b1plot, $b2plot]);
        $graph->Add($gbplot);
        $graph->legend->SetPos(0.5, 0.01, 'center');
        $graph->legend->SetColor('#8f8f8f');
        $graph->legend->font_size = 9;

        if ($isDesktop) {
            $graph->SetMargin(60, 20, 51, 60);
        } else {
            $graph->SetMargin(50, 10, 51, 60);
        }

        $b1plot->SetFillColor('#4682c3');
        $b1plot->SetLegend('Points Earned');
        $b1plot->SetWidth($barWidth);
        $b1plot->SetWeight(0);

        $formatNumber = function ($label) {
            return ' ' . $this->localizer->formatNumber($label);
        };
        $b1plot->value->SetFormatCallback($formatNumber);
        $b1plot->value->SetColor('#4682c3');
        $b1plot->value->SetAngle(90);
        $b1plot->value->Show();

        $b2plot->SetLegend('Earning Potential');
        $b2plot->SetFillColor('#4abea0');
        $b2plot->SetWeight(0);
        $b2plot->SetWidth($barWidth);

        $b2plot->value->SetFormatCallback($formatNumber);
        $b2plot->value->SetColor('#4abea0');
        $b2plot->value->SetAngle(90);
        $b2plot->value->Show();

        $headers = [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="card-spend-' . $device . '.png"',
        ];

        return new Response($graph->Stroke(), 200, $headers);
    }

    private function getEarningPotentialLastSuccessCheckDate(): ?\DateTime
    {
        $conn = $this->em->getConnection();
        $sql = $this->em->getRepository(Account::class)
            ->getAccountsSQLByUserAgent(
                $this->tokenStorage->getUser()->getId(),
                sprintf(' AND p.ProviderID IN (%s)', implode(', ', Provider::EARNING_POTENTIAL_LIST)),
            );
        $result = $conn->executeQuery("$sql ORDER BY SuccessCheckDate DESC LIMIT 1")->fetchAssociative();

        if ($result === false) {
            return null;
        }

        return new \DateTime($result['SuccessCheckDate']);
    }

    /**
     * @throws BadRequestHttpException
     */
    private function checkSubAccountId(int $id): Subaccount
    {
        $subAccRepo = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Subaccount::class);
        $subAcc = $subAccRepo->find($id);

        if (!$subAcc instanceof Subaccount) {
            throw new BadRequestHttpException('Unavailable SubAccount');
        }

        //        if (!$this->checker->isGranted('READ_HISTORY', $subAcc->getAccountid())) {
        //            throw new AccessDeniedHttpException('Access Denied.');
        //        }

        return $subAcc;
    }
}
