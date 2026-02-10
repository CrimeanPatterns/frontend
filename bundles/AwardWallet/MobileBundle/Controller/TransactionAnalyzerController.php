<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Repositories\ProviderRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\Mobile\SpentAnalysis\TransactionAnalysisType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileMileValueFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileSpentAnalysisFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileTransactionAnalyzerFormatter;
use AwardWallet\MainBundle\Service\AccountHistory\NextPageToken;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionAnalyzerService;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionQuery;
use AwardWallet\MainBundle\Service\AccountHistory\TransactionQueryCondition;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/transactions")
 */
class TransactionAnalyzerController extends AbstractController
{
    use JsonTrait;

    /**
     * @var MobileTransactionAnalyzerFormatter
     */
    private $mobileTransactionAnalyzerFormatter;
    /**
     * @var TransactionAnalyzerService
     */
    private $transactionsService;
    /**
     * @var BankTransactionsAnalyser
     */
    private $bankTransactionsAnalyser;
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var FormDehydrator
     */
    private $formDehydrator;
    /**
     * @var FormFactoryInterface
     */
    private $formFactory;
    /**
     * @var MobileSpentAnalysisFormatter
     */
    private $mobileSpentAnalysisFormatter;
    /**
     * @var ProviderRepository
     */
    private $providerRepository;
    private MileValueService $mileValueService;
    private MobileMileValueFormatter $mobileMileValueFormatter;

    public function __construct(
        TransactionAnalyzerService $transactionsService,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        MobileTransactionAnalyzerFormatter $mobileTransactionAnalyzerFormatter,
        AwTokenStorageInterface $tokenStorage,
        FormDehydrator $formDehydrator,
        FormFactoryInterface $formFactory,
        MobileSpentAnalysisFormatter $mobileSpentAnalysisFormatter,
        ProviderRepository $providerRepository,
        MileValueService $mileValueService,
        MobileMileValueFormatter $mobileMileValueFormatter,
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
        $this->mobileTransactionAnalyzerFormatter = $mobileTransactionAnalyzerFormatter;
        $this->transactionsService = $transactionsService;
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->tokenStorage = $tokenStorage;
        $this->formDehydrator = $formDehydrator;
        $this->formFactory = $formFactory;
        $this->mobileSpentAnalysisFormatter = $mobileSpentAnalysisFormatter;
        $this->providerRepository = $providerRepository;
        $this->mileValueService = $mileValueService;
        $this->mobileMileValueFormatter = $mobileMileValueFormatter;
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/data", methods={"POST"})
     * @JsonDecode()
     */
    public function dataAction(Request $request): JsonResponse
    {
        $request->getSession()->save();
        $requestData = $request->request->all();
        $nextPageToken = !empty($requestData['nextPageToken']) ?
            NextPageToken::createFromString($requestData['nextPageToken']) :
            null;
        $descriptionFilter = StringUtils::isNotEmpty($requestData['descriptionFilter'] ?? '') ?
            $requestData['descriptionFilter'] :
            null;
        $formData = $requestData['form'] ?? [];

        try {
            /** @var FormInterface $form */
            [$form, $queryExtraData, $eagerData, $defaultRange] = $this->prepareForm($this->tokenStorage->getUser(), !$formData);
        } catch (NotFoundHttpException $e) {
            return $this->jsonResponse(['eligibleProviders' => $this->mobileSpentAnalysisFormatter->getEligibleProvidersList()]);
        }

        $defaultOwner = \key($queryExtraData['ownersList']);

        if ($formData) {
            $request->request->replace(['mobile_transaction_analysis' => $formData]);
            $form->handleRequest($request);
            $offerFilterIds = self::createOfferFilterIdsList($form);

            if ($form->isSubmitted() && $form->isValid()) {
                $defaultOwner = $form->get('owner')->getData();
            } else {
                try {
                    [$form, $queryExtraData, $eagerData, $defaultRange] = $this->prepareForm($this->tokenStorage->getUser(), true);
                } catch (NotFoundHttpException $e) {
                    return $this->jsonResponse(['eligibleProviders' => $this->mobileSpentAnalysisFormatter->getEligibleProvidersList()]);
                }

                $eagerData['formReset'] = true;
            }
        } else {
            $offerFilterIds = self::createOfferFilterIdsList($form);
        }

        $result = [
            'form' => $this->formDehydrator->dehydrateForm($form, false),
            'filters' => $this->getFilters(
                $defaultRange,
                $queryExtraData['accounts'],
                $queryExtraData['ownersList'][$defaultOwner]
            ),
            'mileValue' => $this->mobileMileValueFormatter->formatShortList($this->mileValueService->getData(false)),
            'offerFilterIds' => $offerFilterIds,
        ];

        if ($eagerData) {
            return $this->jsonResponse(\array_merge(
                $result,
                $eagerData
            ));
        }

        $query = self::createQueryByFormData(
            $queryExtraData,
            $form->getData(),
            $requestData['filters'] ?? [],
            $descriptionFilter,
            $nextPageToken
        );

        [$transactions, $newNextPageToken, $isLastPageLoaded] = $this->transactionsService->getTransactions($query);

        $result = array_merge(
            $result,
            [
                'transactions' => $this->mobileTransactionAnalyzerFormatter->formatTransactions($transactions),
                'nextPageToken' => $isLastPageLoaded ? null : $newNextPageToken,
            ],
            $transactions ?
                [] :
                ['eligibleProviders' => $this->mobileSpentAnalysisFormatter->getEligibleProvidersList()]
        );

        return $this->jsonResponse($result);
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/totals", methods={"POST"})
     * @JsonDecode()
     */
    public function totalsAction(Request $request)
    {
        $request->getSession()->save();
        $requestData = $request->request->all();
        $descriptionFilter = StringUtils::isNotEmpty($requestData['descriptionFilter'] ?? '') ?
            $requestData['descriptionFilter'] :
            null;

        [$form, $queryExtraData] = $this->prepareForm($this->tokenStorage->getUser(), false);
        $request->request->replace(['mobile_transaction_analysis' => $requestData['form'] ?? []]);
        $form->handleRequest($request);

        if (!($form->isSubmitted() && $form->isValid())) {
            return $this->jsonResponse(['totals' => []]);
        }

        $query = self::createQueryByFormData(
            $queryExtraData,
            $form->getData(),
            $requestData['filters'] ?? [],
            $descriptionFilter
        );
        $query->setWithEarningPotential(true);

        return $this->jsonResponse([
            'totals' => $this->mobileTransactionAnalyzerFormatter->formatTotals($this->transactionsService->getTotals($query)),
        ]);
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/categories", methods={"POST"})
     * @JsonDecode()
     */
    public function categoriesAction(Request $request): JsonResponse
    {
        $request->getSession()->save();
        $requestData = $request->request->all();
        [$form, $queryExtraData] = $this->prepareForm($this->tokenStorage->getUser(), false);
        $request->request->replace(['mobile_transaction_analysis' => $requestData['form'] ?? []]);
        $form->handleRequest($request);

        if (!($form->isSubmitted() && $form->isValid())) {
            return $this->jsonResponse(['categories' => []]);
        }

        $query = self::createQueryByFormData(
            $queryExtraData,
            $form->getData(),
            []
        );
        $query->setWithEarningPotential(false);

        return $this->jsonResponse([
            'categories' => it($this->transactionsService->getTotals($query)->categories ?? [])
                ->values()
                ->collect()
                ->unique()
                ->map(function (string $category): array {
                    return [
                        'name' => $category,
                        'value' => $category,
                    ];
                })
                ->toArray(),
        ]);
    }

    public function prepareForm(Usr $user, bool $initialMerchantData): array
    {
        $analysisData = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($user, true);
        $analysisData['ownersList'] =
            it($analysisData['ownersList'] ?? [])
            ->filter(function ($owner) { return \count($owner['availableCards'] ?? []) > 0; })
            ->toArrayWithKeys();

        if (!$analysisData['ownersList']) {
            throw $this->createNotFoundException();
        }

        $formData = [
            'offer_cards' => [],
            'owner' => \key($analysisData['ownersList']),
            'select_filter' => 'all',
            'definition' => [
                'ownersList' => $analysisData['ownersList'],
                'cards' => it([]),
                'providerData' => [],
            ],
        ];

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
            ];
        }

        $formData['definition']['cards'] = $formData['definition']['cards']->toArrayWithKeys();
        $eagerData = null;
        $defaultRange = BankTransactionsDateUtils::THIS_YEAR;

        if ($initialMerchantData) {
            $query = self::createQueryByFormData($analysisData, $formData, []);
            $transactions = [];

            foreach (
                [
                    BankTransactionsDateUtils::THIS_YEAR,
                    BankTransactionsDateUtils::LAST_YEAR,
                ] as $defaultRange
            ) {
                $query->setOfferCards([]);
                $query->setWithEarningPotential(true);
                $this->modifyRange($query, $defaultRange);
                [$transactions, $nextPageToken, $isLastPageLoaded] = $this->transactionsService->getTransactions($query);

                if ($transactions) {
                    break;
                }
            }

            if (!$transactions) {
                throw $this->createNotFoundException();
            }

            $eagerData = [
                'transactions' => $this->mobileTransactionAnalyzerFormatter->formatTransactions($transactions),
                'nextPageToken' => $isLastPageLoaded ? null : $nextPageToken,
            ];
        }

        $form = $this->formFactory->create(TransactionAnalysisType::class, $formData, ['csrf_protection' => false]);

        return [$form, $analysisData, $eagerData, $defaultRange];
    }

    /**
     * @return list<int>
     */
    protected static function createOfferFilterIdsList(FormInterface $form): array
    {
        return it($form->get('offer_cards'))
            ->flatten(1)
            ->filter(fn (FormInterface $form) => \is_bool($enabled = $form->getData()) && $enabled)
            ->map(fn (FormInterface $form) => (int) \explode(':', $form->getName())[2])
            ->toArray();
    }

    protected function getFilters(int $defaultRange, array $accounts, array $ownerData)
    {
        return [
            [
                'type' => 'date_range',
                'name' => 'date_range',
                'title' => 'Date Range',
                'default' => $defaultRange,
                'ranges' => [
                    [
                        'value' => BankTransactionsDateUtils::THIS_MONTH,
                        'title' => 'This Month',
                    ],
                    [
                        'value' => BankTransactionsDateUtils::LAST_MONTH,
                        'title' => 'Last Month',
                    ],
                    [
                        'value' => BankTransactionsDateUtils::THIS_QUARTER,
                        'title' => 'This Quarter',
                    ],
                    [
                        'value' => BankTransactionsDateUtils::LAST_QUARTER,
                        'title' => 'Last Quarter',
                    ],
                    [
                        'value' => BankTransactionsDateUtils::THIS_YEAR,
                        'title' => 'This Year',
                    ],
                    [
                        'value' => BankTransactionsDateUtils::LAST_YEAR,
                        'title' => 'Last Year',
                    ],
                    [
                        'value' => 0,
                        'title' => 'All transactions',
                    ],
                ],
            ],
            [
                'type' => 'choice',
                'name' => 'credit_card',
                'title' => 'Credit Card',
                'choices' => $this->getProviders($accounts, $ownerData),
            ],
            [
                'type' => 'category',
                'name' => 'categories',
                'title' => 'Category',
            ],
            [
                'type' => 'amount',
                'name' => 'amount',
                'title' => 'Amount',
            ],
            [
                'type' => 'choice',
                'name' => 'point_multiplier',
                'title' => 'Point Multiplier',
                'choices' => it([1, 2, 3, 4, 5])
                    ->map(function (int $value) {
                        return [
                            'value' => $value,
                            'name' => "{$value}x",
                        ];
                    })
                    ->toArray(),
            ],
            [
                'type' => 'choice',
                'name' => 'earning_potential',
                'title' => 'Earning Potential',
                'choices' => it([1, 2, 3, 4])
                    ->map(function (int $value) {
                        return [
                            'value' => $value,
                            'name' => "{$value}x difference",
                        ];
                    })
                    ->toArray(),
            ],
        ];
    }

    protected function getProviders(array $accounts, array $ownerData)
    {
        $existed = [];

        foreach ($accounts as $item) {
            if (!isset($existed[$item['providerCode']])) {
                $existed[$item['providerCode']] = 0;
            }
            $existed[$item['providerCode']]++;
        }
        $providers = $this->providerRepository->findBy([
            'providerid' => Provider::EARNING_POTENTIAL_LIST,
        ]);
        $providersSorted = [];

        /** @var Provider $provider */
        foreach ($providers as $provider) {
            $choices =
                it($ownerData['availableCards'])
                ->filter(function (array $cardData) use ($provider) {
                    return $provider->getProviderid() == $cardData['providerId'];
                })
                ->map(function (array $cardData) {
                    return [
                        'name' => $cardData['creditCardName'],
                        'value' => $cardData['subAccountId'],
                    ];
                })
                ->toArray();

            if (!$choices) {
                continue;
            }

            $newIndex = array_search($provider->getProviderid(), Provider::EARNING_POTENTIAL_LIST);
            $providersSorted[$newIndex] = [
                'value' => $provider->getProviderid(),
                'name' => $provider->getDisplayname(),
                'choices' => $choices,
            ];
        }
        ksort($providersSorted);

        return \array_values($providersSorted);
    }

    protected function modifyRange(TransactionQuery $query, int $range): TransactionQuery
    {
        ['start' => $start, 'end' => $end] =
            it(BankTransactionsDateUtils::findRangeLimits($range))
            ->map(function (string $date) { return new \DateTime($date); })
            ->toArrayWithKeys();

        $query->setRangeLimits(
            $start,
            $end
        );

        return $query;
    }

    protected static function createQueryByFormData(array $queryExtraData, array $formData, array $filters, ?string $descriptionFilter = null, ?NextPageToken $nextPageToken = null): TransactionQuery
    {
        $subAccIds =
            $filters['credit_card'] ??
                it($queryExtraData['ownersList'][$formData['owner']]['availableCards'] ?? [])
                ->column('subAccountId')
                ->toArray();
        $dateRange = $filters['date_range']['range'] ?? null;
        $startDate = $filters['date_range']['start_date'] ?? null;
        $endDate = $filters['date_range']['end_date'] ?? null;
        $categories = $filters['categories'] ?? null;
        $amountLess = $filters['amount']['less'] ?? null;
        $amountGreater = $filters['amount']['greater'] ?? null;
        $amountExactly = $filters['amount']['exactly'] ?? null;
        $multiplier = $filters['point_multiplier'] ?? null;
        $potential = $filters['earning_potential'] ?? null;

        $query =
            (new TransactionQuery($subAccIds, $descriptionFilter, $nextPageToken))
            ->setOfferCards(
                it($formData['offer_cards'])
                ->flatten(1)
                ->flatMapIndexed(function (bool $value, string $name) use ($formData) {
                    if ($value) {
                        yield $formData['definition']['cards'][$name]['creditCardId'];
                    }
                })
                ->toArray()
            );

        if ($categories) {
            $query->setCategories($categories);
        }

        if ((int) $dateRange > 0) {
            $range = BankTransactionsDateUtils::findRangeLimits((int) $dateRange);
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

        if ($amountLess || $amountGreater || $amountExactly) {
            $query->setAmountCondition(new TransactionQueryCondition((float) $amountLess, (float) $amountGreater, (float) $amountExactly));
        }

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
