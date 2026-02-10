<?php

namespace AwardWallet\MainBundle\Service\CreditCards;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsDateUtils;
use AwardWallet\MainBundle\Service\AccountHistory\SpentAnalysisService;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\ORM\EntityManagerInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class UserSpending
{
    private EntityManagerInterface $entityManager;
    private SpentAnalysisService $spentAnalysisService;
    private BankTransactionsAnalyser $bankTransactionsAnalyser;
    private MileValueService $mileValueService;
    private MileValueCards $mileValueCards;
    private CacheManager $cacheManager;
    private \HttpDriverInterface $curlDriver;

    private array $cardCategories;
    private string $openAiApiKey;

    public function __construct(
        EntityManagerInterface $entityManager,
        CacheManager $cacheManager,
        SpentAnalysisService $spentAnalysisService,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        MileValueService $mileValueService,
        MileValueCards $mileValueCards,
        \HttpDriverInterface $curlDriver,
        string $openAiApiKey
    ) {
        $this->entityManager = $entityManager;
        $this->cacheManager = $cacheManager;
        $this->spentAnalysisService = $spentAnalysisService;
        $this->bankTransactionsAnalyser = $bankTransactionsAnalyser;
        $this->mileValueService = $mileValueService;
        $this->mileValueCards = $mileValueCards;
        $this->openAiApiKey = $openAiApiKey;
        $this->curlDriver = $curlDriver;
    }

    public function getExistingCards(int $userId, bool $excludeClosed = true): array
    {
        return $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                    c.CreditCardID, COALESCE(c.CardFullName, c.Name) AS CardName,
                    ucc.EarliestSeenDate, ucc.LastSeenDate, ucc.IsClosed,
                    p.DisplayName
            FROM UserCreditCard ucc
            JOIN CreditCard c ON (c.CreditCardID = ucc.CreditCardID)
            LEFT JOIN Provider p ON (p.ProviderID = c.ProviderID)
            WHERE
                    ucc.UserID = ?
                AND ' . ($excludeClosed ? 'ucc.IsClosed = 0' : '1') . '
        ',
            [$userId], [\PDO::PARAM_INT]
        );
    }

    public function getLastTransactions(int $userId): array
    {
        $user = $this->entityManager->getRepository(Usr::class)->find($userId);
        $haveCards = $this->getExistingCards($userId);
        $analysisData = $this->bankTransactionsAnalyser->getSpentAnalysisInitial($user, true);

        $subAccountsId = array_column(
            $analysisData['ownersList'][$userId . '_0']['availableCards'] ?? [],
            'subAccountId'
        );
        $cardsId = $this->getCardsIdFromAnalysis($analysisData);

        $transactions = $this->spentAnalysisService->merchantsData(
            $subAccountsId,
            BankTransactionsDateUtils::LAST_QUARTER,
            $cardsId,
            50
        );

        $result = [];

        foreach ($transactions['data'] as &$item) {
            unset($item['merchant']);

            if (!array_key_exists('cardId', $item)) {
                continue;
            }

            $cardId = $item['cardId'];
            $date = new \DateTime('@' . strtotime($item['postingDate']));
            $dateKey = $date->format('Y-m');
            $category = $item['category'];

            if (empty($category)) {
                continue;
            }

            if (!array_key_exists($cardId, $result)) {
                $result[$cardId] = [];
            }

            if (!array_key_exists($dateKey, $result[$cardId])) {
                $result[$cardId][$dateKey] = [];
            }

            if (!array_key_exists($category, $result[$cardId][$dateKey])) {
                $result[$cardId][$dateKey][$category] = [];
            }

            $result[$cardId][$dateKey][$category][] = $item;
        }

        $costs = [];

        foreach ($result as $cardId => &$dates) {
            foreach ($dates as $date => &$categories) {
                foreach ($categories as $cat => &$transactions) {
                    $totalSpend = array_sum(array_column($transactions, 'amount'));
                    $miles = array_sum(array_column($transactions, 'miles'));
                    $milesBalance = $miles;

                    $mileValueCost = array_key_exists($cardId, $costs)
                        ? $costs[$cardId]
                        : ($costs[$cardId] = $this->mileValueCards->getCardMileValueCost($cardId,
                            $this->spentAnalysisService->getCacheContext()));

                    if (null === $mileValueCost->getPrimaryValue()) {
                        $result[$cardId][$date][$cat] = null;

                        continue;
                    }

                    if (CreditCard::CASHBACK_TYPE_POINT === $mileValueCost->getCashBackType()) {
                        $milesBalance *= 100;
                    }

                    $cashEquivalent = $this->mileValueService->calculateCashEquivalent(
                        $mileValueCost->getPrimaryValue(),
                        $milesBalance,
                        null
                    );

                    $result[$cardId][$date][$cat] = [
                        'spend' => $totalSpend,
                        'earned' => $miles,
                        'cashEquivalent' => $cashEquivalent['raw'],
                    ];
                }
            }
        }

        $data = ['cards' => []];

        foreach ($haveCards as $haveCard) {
            $cardId = (int) $haveCard['CreditCardID'];

            if (!array_key_exists($cardId, $costs)) {
                $costs[$cardId] = $this->mileValueCards->getCardMileValueCost(
                    $cardId,
                    $this->spentAnalysisService->getCacheContext()
                );
            }

            if (null === $costs[$cardId]->getPrimaryValue()) {
                // throw new \Exception('Unknown mile value cost for cardId ' . $cardId);
                // continue;
            }

            $card = [
                'cardId' => $cardId,
                'cardName' => $haveCard['CardName'],
                'issuingBank' => $haveCard['DisplayName'],
                'earliestSeenDate' => $haveCard['EarliestSeenDate'],
                'lastSeenDate' => $haveCard['LastSeenDate'],
                'monthlySpend' => [],
            ];

            if (array_key_exists($cardId, $result)) {
                $monthlySpend = [];

                foreach ($result[$cardId] as $dateKey => $categories) {
                    $date = new \DateTime($dateKey . '-05 12:00:00');
                    $totalSpend = array_sum(array_column($categories, 'spend'));

                    $monthSpend = [
                        'month' => $date->format('F Y'),
                        'total spend (USD)' => $totalSpend,
                        'categoryBreakdown' => [],
                    ];

                    foreach ($categories as $category => $total) {
                        if (empty($total)) {
                            continue;
                        }

                        $monthSpend['categoryBreakdown'][] = [
                            'category' => $category,
                            'totalSpend' => $total['spend'],
                            'pointsEarned' => $total['earned'],
                            'pointCashEquivalent (USD)' => $total['cashEquivalent'],
                        ];
                    }

                    $monthlySpend[] = $monthSpend;
                }

                $card['monthlySpend'] = $monthlySpend;
            }

            $data['cards'][] = $card;
        }

        return $data;
    }

    public function getAvailableCars(): array
    {
        $cards = $this->entityManager->getConnection()->fetchAllAssociative('
            SELECT
                c.CreditCardID, COALESCE(c.CardFullName, c.Name) AS CardName, c.Description, c.IsBusiness, c.IsDiscontinued, c.IsCashBackOnly,
                p.DisplayName, p.Currency,
                cr.Name AS CurrencyName
            FROM CreditCard c
            LEFT JOIN Provider p ON (p.ProviderID = c.ProviderID)
            LEFT JOIN Currency cr ON (cr.CurrencyID = p.Currency)
        ');

        $list = [];

        foreach ($cards as $card) {
            $cardId = (int) $card['CreditCardID'];
            $mileValueCost = $this->mileValueCards->getCardMileValueCost($cardId,
                $this->spentAnalysisService->getCacheContext());

            $item = [
                'cardId' => $cardId,
                'cardName' => $card['CardName'],
                'issuingBank' => $card['DisplayName'],
                'cardType' => 0 === (int) $card['IsBusiness'] ? 'business' : 'personal',
                'isCashback' => 1 === (int) $card['IsCashBackOnly'] ? 'true' : 'false',
                'isDiscontinued' => 1 === (int) $card['IsDiscontinued'] ? 'true' : 'false',
                'shortEarningDescription' => $card['Description'],
                'awardWalletPointValue' => $mileValueCost->getPrimaryValue(),
                'currencyName' => $card['CurrencyName'],
                'earningCategories' => [],
            ];

            $creditCardCategoryList = $this->getCardCategories()[$cardId] ?? [];

            foreach ($creditCardCategoryList as $category) {
                $item['earningCategories'][] = [
                    'categoryId' => $category['ID'],
                    'categoryName' => $category['Name'],
                    'multiplier' => $category['Multiplier'],
                    'startDate' => $category['StartDate'] ?? null,
                    'endDate' => $category['EndDate'] ?? null,
                    'description' => strip_tags($category['Description']),
                ];
            }

            $list[] = $item;
        }

        return ['cards' => $list];
    }

    public function sendAi(string $prompt)
    {
        $payload = [
            'model' => \AwardWallet\MainBundle\Service\AIModel\OpenAI\Request::MODEL_CHATGPT_4O_LATEST,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $httpResponse = $this->curlDriver->request(
            new \HttpDriverRequest(
                'https://api.openai.com/v1/chat/completions',
                'POST',
                json_encode($payload),
                [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                ],
                500
            ),
        );

        $decoded = @json_decode($httpResponse->body, true);

        $message = '';

        if (!empty($decoded['choices'])) {
            foreach ($decoded['choices'] as $choice) {
                $message .= '<pre>' . var_export(json_decode($choice['message']['content'], true), true) . '</pre><hr>';
            }
            $message = str_replace('array', '', $message);
        }

        return $message;
    }

    private function getCardsIdFromAnalysis(array $data): array
    {
        $ids = [];

        foreach ($data['offerCardsFilter'] as $item) {
            $ids = array_merge($ids, array_column($item['cardsList'], 'creditCardId'));
        }

        return $ids;
    }

    private function getCardCategories(): array
    {
        if (empty($this->cardCategories)) {
            $this->cardCategories = it($this->entityManager->getConnection()->executeQuery("
                select * from (
                    select 
                        'CreditCardShoppingCategoryGroup' as SchemaName,
                        scg.Name,
                        ccscg.CreditCardID,
                        ccscg.CreditCardShoppingCategoryGroupID as ID,
                        ccscg.Multiplier,
                        ccscg.StartDate, ccscg.EndDate,
                        case when scg.Name is null then 0 else 1 end HaveGroup,
                        ccscg.Description
                    from
                        CreditCardShoppingCategoryGroup ccscg
                        left join ShoppingCategoryGroup scg on ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                    where
                        ccscg.EndDate is null or ccscg.EndDate > now()
                    union select
                        'CreditCardMerchantGroup' as SchemaName,
                        mg.Name,
                        ccmg.CreditCardID,
                        ccmg.CreditCardMerchantGroupID as ID,
                        ccmg.Multiplier,
                        ccmg.StartDate, ccmg.EndDate,
                        1 as HaveGroup,
                        ccmg.Description
                    from
                        CreditCardMerchantGroup ccmg
                        join MerchantGroup mg on ccmg.MerchantGroupID = mg.MerchantGroupID
                    where
                        ccmg.EndDate is null or ccmg.EndDate > now()
                ) a
                order by 
                    CreditCardID,
                    HaveGroup,
                    Multiplier DESC,     
                    Name
        ")->fetchAllAssociative())
                ->reindex(fn ($row) => $row['CreditCardID'])
                ->collapseByKey()
                ->toArrayWithKeys();
        }

        return $this->cardCategories;
    }
}
