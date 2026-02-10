<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Type\Mobile\SpentAnalysis\SpentAnalysisType;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\DynamicUtils;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileMileValueFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileSpentAnalysisFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQuery;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisQueryHandler;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/account/spent-analysis")
 */
class SpentAnalysisController extends AbstractController
{
    use JsonTrait;

    private const MERCHANTS_LIST_LIMIT = 15;

    private SpentAnalysisQueryHandler $spentAnalysisRequestHandler;
    private MobileSpentAnalysisFormatter $mobileSpentAnalysisFormatter;
    private MileValueService $mileValueService;
    private MobileMileValueFormatter $mobileMileValueFormatter;

    public function __construct(
        SpentAnalysisQueryHandler $spentAnalysisRequestHandler,
        MobileSpentAnalysisFormatter $mobileSpentAnalysisFormatter,
        MileValueService $mileValueService,
        MobileMileValueFormatter $mobileMileValueFormatter,
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
        $this->spentAnalysisRequestHandler = $spentAnalysisRequestHandler;
        $this->mobileSpentAnalysisFormatter = $mobileSpentAnalysisFormatter;
        $this->mileValueService = $mileValueService;
        $this->mobileMileValueFormatter = $mobileMileValueFormatter;
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/transaction-offer", name="awm_spent_analysis_transaction_offer", methods={"POST"})
     * @Template("@AwardWalletMain/SpentAnalysis/offer.html.twig")
     * @JsonDecode()
     */
    public function transactionOfferAction(
        Request $request,
        \AwardWallet\MainBundle\Controller\SpentAnalysisController $spentAnalysisController
    ): array {
        return $spentAnalysisController->getTransactionOfferData($request);
    }

    /**
     * Security("is_granted('CSRF')").
     *
     * @Route("/transaction-card-offer", name="awm_spent_analysis_transaction_card_offer", methods={"POST"})
     * @JsonDecode()
     */
    public function transactionCardOffer(
        Request $request,
        SpentAnalysisService $spentAnalysisService,
        \AwardWallet\MainBundle\Controller\SpentAnalysisController $spentAnalysisController
    ): JsonResponse {
        $offerData = $this->transactionOfferAction($request, $spentAnalysisController);
        $offersHtml = $this->render("@AwardWalletMain/SpentAnalysis/offer.html.twig", $offerData)->getContent();
        $result = $spentAnalysisService->fetchTransactionOfferWithBestCard($offerData);
        $result['offersHtml'] = $offersHtml;

        return new JsonResponse($result);
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/merchants/data", methods={"POST"})
     * @JsonDecode()
     */
    public function dataAction(
        Request $request,
        ApiVersioningService $apiVersioning,
        FormDehydrator $formDehydrator,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        TranslatorInterface $translator
    ): JsonResponse {
        if (
            !$this->isGranted('USER_AWPLUS')
            // prevent from possible bar-charts blur issues on size(fake_data) > size(real_data) account
            && $apiVersioning->notSupports(MobileVersions::SPENT_ANALYSIS_OPEN_FOR_AW_FREE)
            && $apiVersioning->supportsAll([
                MobileVersions::SPENT_ANALYSIS_FAKE_DATA_FOR_AW_FREE,
                MobileVersions::NATIVE_APP,
            ])
        ) {
            return $this->jsonResponse($this->getRandomData($bankTransactionsAnalyser, $translator));
        }

        $session = $request->getSession();

        if ($session) {
            $session->save();
        }

        $requestData = $request->request->all();

        /** @var FormInterface $form */
        try {
            [$form, $queryExtraData, $eagerData] = $this->prepareForm(!$requestData, $bankTransactionsAnalyser);
        } catch (NotFoundHttpException $e) {
            return $this->jsonResponse(['eligibleProviders' => $this->mobileSpentAnalysisFormatter->getEligibleProvidersList()]);
        }

        if ($requestData) {
            $request->request->replace(['mobile_spent_analysis' => $requestData]);
            $form->handleRequest($request);
        }

        $result = [
            'form' => $formDehydrator->dehydrateForm($form, false),
            'mileValue' => $this->mobileMileValueFormatter->formatShortList($this->mileValueService->getData(false)),
        ];

        if ($eagerData) {
            $result['analysis'] = $eagerData;

            return $this->jsonResponse($result);
        }

        $query = SpentAnalysisQuery::createMerchantDataQueryByForm($form)
            ->setFormatter($this->mobileSpentAnalysisFormatter)
            ->setExtraData([$form->getData(), $queryExtraData])
            ->setLimit(self::MERCHANTS_LIST_LIMIT);

        try {
            $result['analysis'] = $this->spentAnalysisRequestHandler->handleRequest($query);
        } catch (\InvalidArgumentException $e) {
            $result['analysis'] = $this->mobileSpentAnalysisFormatter->format(['data' => []], $query);
        } catch (AccessDeniedException $e) {
            throw $this->createNotFoundException();
        }

        if (!($result['analysis']['rows'] ?? [])) {
            $result['analysis']['accounts'] = !empty($queryExtraData['accounts'])
                ? ($queryExtraData['ownersList'][$form->getData()['owner'] ?? 0]['accountsId'] ?? [])
                : [];
            $result['eligibleProviders'] = $this->mobileSpentAnalysisFormatter->getEligibleProvidersList();
        }

        return $this->jsonResponse($result);
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/merchants/transactions/{merchantId}",
     *     methods={"POST"},
     *     requirements={"merchantId" = "\d+"}
     * )
     * @JsonDecode()
     */
    public function transactionsAction(Request $request, int $merchantId, BankTransactionsAnalyser $bankTransactionsAnalyser): JsonResponse
    {
        $session = $request->getSession();

        if ($session) {
            $session->save();
        }

        /** @var FormInterface $form */
        [$form, $queryExtraData] = $this->prepareForm(false, $bankTransactionsAnalyser);
        $requestData = $request->request->all();

        if ($requestData) {
            $request->request->replace(['mobile_spent_analysis' => $requestData]);
            $form->handleRequest($request);
        }

        $query = SpentAnalysisQuery::createMerchantTransactionQueryByForm($form, $merchantId)
            ->setFormatter($this->mobileSpentAnalysisFormatter)
            ->setExtraData([$form->getData(), $queryExtraData]);

        try {
            return $this->jsonResponse($this->spentAnalysisRequestHandler->handleRequest($query));
        } catch (\InvalidArgumentException|AccessDeniedException $e) {
            return $this->jsonResponse([]);
        }
    }

    protected function prepareForm(bool $initialMerchantData, BankTransactionsAnalyser $bankTransactionsAnalyser): array
    {
        $analysisData = $bankTransactionsAnalyser->getSpentAnalysisInitial();

        $analysisData['ownersList'] =
            it($analysisData['ownersList'] ?? [])
            ->filter(function ($owner) { return \count($owner['availableCards'] ?? []) > 0; })
            ->toArrayWithKeys();

        if (!$analysisData['ownersList']) {
            throw $this->createNotFoundException();
        }

        $formData = [
            'owner_cards' => [],
            'offer_cards' => [],
            'owner' => \key($analysisData['ownersList']),
            'date_range' => BankTransactionsDateUtils::THIS_QUARTER,
            'select_filter' => 'all',
            'definition' => [
                'ownersList' => $analysisData['ownersList'],
                'cards' => it([]),
                'dateRanges' => $analysisData['dateRanges'],
                'providerData' => [],
            ],
        ];

        foreach ($analysisData['ownersList'] as $ownerId => $ownerData) {
            $definition =
                it($ownerData['availableCards'])
                ->reindex(function (array $card) use ($ownerId): string {
                    return "owner_card:{$ownerId}:{$card['subAccountId']}";
                })
                ->toArrayWithKeys();

            $formData['owner_cards']["owner_cards:{$ownerId}"] =
                it($definition)
                ->column('subAccountId')
                ->toArray();
        }

        foreach ($analysisData['offerCardsFilter'] as $offerProviderData) {
            $definition =
                it($offerProviderData['cardsList'])
                ->reindex(function (array $card) use ($offerProviderData): string {
                    return "offer_card:{$offerProviderData['providerId']}:{$card['creditCardId']}";
                })
                ->toArrayWithKeys();

            $formData['offer_cards']['offer_cards:' . $offerProviderData['providerId']] =
                it($definition)
                ->map(function () { return true; })
                ->toArrayWithKeys();

            $formData['definition']['cards']->chain($definition);
            $formData['definition']['providerData']['offer_cards:' . $offerProviderData['providerId']] = [
                'id' => $offerProviderData['providerId'],
                'name' => $offerProviderData['displayName'],
                'code' => $offerProviderData['providerCode'],
            ];
        }

        $formData['definition']['cards'] = $formData['definition']['cards']->toArrayWithKeys();
        $thisQuarterEagerData = null;

        if ($initialMerchantData) {
            $query =
                SpentAnalysisQuery::createMerchantDataQueryByFormData($formData)
                ->setFormatter($this->mobileSpentAnalysisFormatter)
                ->setExtraData([$formData, $analysisData])
                ->setLimit(self::MERCHANTS_LIST_LIMIT);

            try {
                $thisQuarterEagerData = $this->spentAnalysisRequestHandler->handleRequest($query);
            } catch (\Exception $e) {
            }

            if (empty($thisQuarterEagerData['rows'])) {
                $formData['date_range'] = BankTransactionsDateUtils::LAST_QUARTER;
                $thisQuarterEagerData = null;
            }
        }

        $form = $this->createForm(SpentAnalysisType::class, $formData, ['csrf_protection' => false]);

        return [$form, $analysisData, $thisQuarterEagerData];
    }

    protected function getRandomData(BankTransactionsAnalyser $bankTransactionsAnalyser, TranslatorInterface $translator)
    {
        $analysisData = $bankTransactionsAnalyser->getSpentAnalysisInitial();
        $analysisData['ownersList'] =
            it($analysisData['ownersList'] ?? [])
            ->filter(function ($owner) { return \count($owner['availableCards'] ?? []) > 0; })
            ->toArrayWithKeys();

        if (!($analysisData['ownersList'] ?? [])) {
            throw $this->createNotFoundException();
        }

        $title = $translator->trans('spent-analysis.merchant.spend-analysis-for', [
            '%date_range%' => $translator->trans("spent-analysis.this-quarter") . ' ',
            '%owner_name%' => it($analysisData['ownersList'])->first()['name'],
        ]);
        $randomHistory = [];

        $rnd = DynamicUtils::createArrayAccessImpl(function (string $range) use (&$randomHistory): string {
            if ('last' === $range) {
                $rnd = $randomHistory[\count($randomHistory) - 1];
            } else {
                [$min, $max] = it(\explode(',', $range))->mapByTrim()->mapToInt()->toArray();
                $rnd = \random_int($min, $max);
                $randomHistory[] = $rnd;
            }

            return (string) $rnd;
        });

        $moneyRnd = DynamicUtils::createToStringImpl(function () {
            return '$' . \random_int(500, 3000);
        });

        $pointsRnd = DynamicUtils::createToStringImpl(function () {
            return \random_int(3000, 10000);
        });

        $numberOfTransactionsRnd = DynamicUtils::createToStringImpl(function () {
            return \random_int(8, 16);
        });

        $multiplierRnd = DynamicUtils::createToStringImpl(function () {
            return \random_int(2, 4) . 'x';
        });

        return [
            "analysis" => [
                "charts" => [
                    [
                        "name" => "APPLE ONLINE STORE",
                        "potentialValue" => $rnd['10000, 20000'],
                        "value" => $rnd['2000, 8000'],
                    ],
                    [
                        "name" => "AMAZON WEB SERVICES",
                        "potentialValue" => $rnd['5000, 10000'],
                        "value" => $rnd['1000, 4000'],
                    ],
                    [
                        "name" => "FLIGHTSTATS INC",
                        "potentialValue" => $rnd['3000, 9000'],
                        "value" => $rnd['500, 2500'],
                    ],
                    [
                        "name" => "Other",
                        "potentialValue" => $rnd['5000, 15000'],
                        "value" => $rnd['1000, 5000'],
                    ],
                ],
                "rows" => [
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "APPLE ONLINE STORE",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "Category:",
                                "value" => "Merchandise & Supplies - Internet Purchase",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "yellow",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "1111",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "AMAZON WEB SERVICES",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "Category:",
                                "value" => "Merchandise & Supplies - Internet Purchase",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "yellow",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "2222",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "FLIGHTSTATS INC",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "yellow",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "3333",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "BURNING MAN # META",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "1x",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "yellow",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "4444",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "HOTELS COM#",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "Category:",
                                "value" => "Travel",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "3x",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "red",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "5555",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "WEGMANS",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "Category:",
                                "value" => "Grocery Stores",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "red",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "6666",
                        "style" => null,
                    ],
                    [
                        "blocks" => [
                            [
                                "kind" => "title",
                                "multiplier" => null,
                                "name" => "DELTA AIR #",
                                "value" => "$moneyRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "# of transactions:",
                                "value" => "$numberOfTransactionsRnd",
                            ],
                            [
                                "kind" => "string",
                                "multiplier" => null,
                                "name" => "Category:",
                                "value" => "Airlines (direct booking)",
                            ],
                            [
                                "kind" => "balance",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Points:",
                                "value" => "$pointsRnd",
                            ],
                            [
                                "color" => "orange",
                                "extraData" => [
                                    "amount" => "{$rnd['2000, 8000']}",
                                    "miles" => "{$rnd['last']}",
                                ],
                                "kind" => "earning_potential",
                                "multiplier" => "$multiplierRnd",
                                "name" => "Earning Potential:",
                                "type" => "offer",
                                "uuid" => null,
                                "value" => "$pointsRnd",
                            ],
                        ],
                        "date" => null,
                        "kind" => "row",
                        "merchant" => "7777",
                        "style" => null,
                    ],
                ],
                "title" => $title,
            ], ];
    }
}
