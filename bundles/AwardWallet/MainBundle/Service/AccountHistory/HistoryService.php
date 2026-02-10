<?php

namespace AwardWallet\MainBundle\Service\AccountHistory;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Merchant;
use AwardWallet\MainBundle\Entity\ShoppingCategory;
use AwardWallet\MainBundle\Entity\ShoppingCategoryGroup;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Loyalty\Resources\HistoryColumn;
use AwardWallet\MainBundle\Loyalty\Resources\ProviderInfoResponse;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\HistoryFormatterInterface;
use AwardWallet\MainBundle\Service\MileValue\MileValueCards;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

class HistoryService
{
    public const VALUE_TYPE_INTEGER = 'integer';
    public const VALUE_TYPE_DECIMAL = 'decimal';
    public const VALUE_TYPE_STRING = 'string';

    public const EARNING_POTENTIAL_COLUMN = 'Earning Potential';
    public const EARNING_POTENTIAL_VALUE_TYPE = 'integer';
    public const CATEGORY_COLUMN = 'Category';
    public const BONUS_COLUMN = 'Bonus';
    public const MILES_COLUMN = 'Miles';
    public const AMOUNT_COLUMN = 'Amount';
    public const INFO_COLUMN = 'Info';

    public const FIELD_TYPE_STRING = 'string';
    public const FIELD_TYPE_OFFER = 'offer';
    public const FIELD_TYPE_THUMB_UP = 'thumb_up';
    public const FIELD_TYPE_EMPTY = 'empty';

    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private Connection $replica;
    private BankTransactionsAnalyser $analyser;
    private UpdaterEngineInterface $updaterEngine;
    private AnalyserContextFactory $contextFactory;
    private MileValueService $mileValueService;
    private MileValueCards $mileValueCards;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $em,
        Connection $replicaUnbufferedConnection,
        BankTransactionsAnalyser $analyser,
        UpdaterEngineInterface $updaterEngine,
        AnalyserContextFactory $contextFactory,
        MileValueService $mileValueService,
        MileValueCards $mileValueCards
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->replica = $replicaUnbufferedConnection;
        $this->analyser = $analyser;
        $this->updaterEngine = $updaterEngine;
        $this->contextFactory = $contextFactory;
        $this->mileValueService = $mileValueService;
        $this->mileValueCards = $mileValueCards;
    }

    public function isHasHistory(HistoryQuery $query): bool
    {
        $account = $query->getAccount();
        $subAccount = $query->getSubAccount();

        $providerInfo = $this->updaterEngine->getProviderInfo($account->getProviderid()->getCode());
        $columns = $this->buildHistoryColumns($providerInfo, $subAccount instanceof Subaccount);

        if (!$providerInfo->getCanparsehistory() || empty($columns)) {
            return false;
        }

        $rows = $subAccount instanceof Subaccount
            ? $this->getSubAccountHistory($subAccount, 1)
            : $this->getAccountHistory($account, 1);

        return !empty($rows);
    }

    public function getHistory(HistoryQuery $query)
    {
        $account = $query->getAccount();
        $subAccount = $query->getSubAccount();

        $providerInfo = $this->updaterEngine->getProviderInfo($account->getProviderid()->getCode());

        $columns = $this->buildHistoryColumns($providerInfo, $subAccount instanceof Subaccount);

        if (!$providerInfo->getCanparsehistory() || empty($columns)) {
            return null;
        }

        $combineBonus = $providerInfo->isCombineHistoryBonusToMiles();
        $bonusColumnIndex = $this->getBonusColumnIndex($columns);

        // ?? нужен ли
        //        $removeMilesColumn = !$combineBonus && array_search('Miles', $columns) === false;
        //        $bonusColumn = array_search('Bonus', $columns);
        //        if ($combineBonus) {[]\
        //            unset($columns[$bonusColumn]);
        //        }

        $postingDate = null;
        /** @var NextPageToken $token */
        $token = $query->getNextPageToken();

        if ($token instanceof NextPageToken) {
            $postingDate = $token->getPostingDate()->format('Y-m-d');
        }

        if ($subAccount instanceof Subaccount) {
            $rows = $this->getSubAccountHistory($subAccount, $query->getLimit(), $query->getDescriptionFilter(), $postingDate);
        } else {
            $rows = $this->getAccountHistory($account, $query->getLimit(), $query->getDescriptionFilter(), $postingDate);
        }

        $data = [];
        $isStarted = true;

        if ($token instanceof NextPageToken) {
            $isStarted = false;
        }

        $cacheContext = $this->contextFactory->makeCacheContext();

        foreach ($rows as $row) {
            if (!$isStarted) {
                $isStarted = $row['UUID'] === $token->getUuid();

                continue;
            }

            [$fields, $milesValue] = $this->buildGeneralFields($row, $columns, $combineBonus);
            $shiftedMilesValue = null;
            $offerData = null;

            if ($subAccount instanceof Subaccount) {
                [$shiftedMilesValue, $offerData] = $this->prepareSubAccountData($account, $fields, $row, $cacheContext, $query);
            }

            $data[] = $this->getHistoryRow($row, $fields, $columns, $milesValue, $shiftedMilesValue, $offerData);
        }

        $result = [
            'historyRows' => $query->getFormatter() instanceof HistoryFormatterInterface ? $query->getFormatter()->format($data, $query) : $data,
            'nextPageToken' => !empty($data) ? (string) new NextPageToken(new \DateTime($row['postingDate']), $row['UUID']) : null,
        ];

        // return only rows for ajax nextPage loading
        if ($token instanceof NextPageToken) {
            return $result;
        }

        $result['accountId'] = $account->getId();
        $result['subAccountId'] = $subAccount instanceof Subaccount ? $subAccount->getId() : null;

        if ($combineBonus) {
            unset($columns[$bonusColumnIndex]);
        }
        $result['historyColumns'] = array_values($columns);

        if ($subAccount instanceof Subaccount) {
            $spentAnalysisInitial = $this->analyser->getSpentAnalysisInitial();
            $result['offerCardsFilter'] = $spentAnalysisInitial['offerCardsFilter'];
        }

        return $result;
    }

    public function exportCsv(HistoryQuery $query): iterable
    {
        $account = $query->getAccount();
        $subAccount = $query->getSubAccount();
        $formatter = $query->getFormatter();
        $providerInfo = $this->updaterEngine->getProviderInfo($account->getProviderid()->getCode());
        $columns = $this->buildHistoryColumns($providerInfo, $subAccount instanceof Subaccount);

        if (!$providerInfo->getCanparsehistory() || empty($columns)) {
            return;
        }

        $csvColumns = $columns;
        $combineBonus = $providerInfo->isCombineHistoryBonusToMiles();

        if ($combineBonus && ($bonusColumnIndex = $this->getBonusColumnIndex($columns))) {
            unset($csvColumns[$bonusColumnIndex]);
        }
        $cacheContext = $this->contextFactory->makeCacheContext();

        if ($subAccount instanceof Subaccount) {
            $rows = $this->getSubAccountHistory($subAccount, null, null, null, false);
        } else {
            $rows = $this->getAccountHistory($account, null, null, null, false);
        }

        yield array_map(static fn (array $column) => $column['name'], $csvColumns);

        foreach ($rows as $row) {
            [$fields, $milesValue] = $this->buildGeneralFields($row, $columns, $combineBonus);
            $shiftedMilesValue = null;
            $offerData = null;

            if ($subAccount instanceof Subaccount) {
                [$shiftedMilesValue, $offerData] = $this->prepareSubAccountData($account, $fields, $row, $cacheContext, $query);
            }

            $data = $this->getHistoryRow($row, $fields, $columns, $milesValue, $shiftedMilesValue, $offerData);

            if ($formatter instanceof HistoryFormatterInterface) {
                $data = $formatter->formatRow($data, $query);
            }

            yield array_map(static fn (array $cell) => $cell['valueFormatted'], $data['cells']);
        }
    }

    private function buildGeneralFields(array $fields, array $columns, $combineBonus)
    {
        $info = @unserialize($fields[self::INFO_COLUMN]);
        $milesValue = 0;
        $result = [];
        $columnsToIgnore = ['', self::EARNING_POTENTIAL_COLUMN];

        foreach ($columns as $column) {
            if (in_array($column['field'], $columnsToIgnore)) {
                continue;
            }

            if ($column['field'] === self::BONUS_COLUMN && $combineBonus) {
                $bonusColumn = $column['name'];

                continue;
            }

            switch ($column['field']) {
                case self::BONUS_COLUMN:
                case self::INFO_COLUMN:
                    $fieldValue = null;

                    if ($info !== false && is_array($info) && isset($info[$column['name']])) {
                        $fieldValue = $info[$column['name']];
                    }

                    break;

                case self::MILES_COLUMN:
                    if (isset($fields[$column['field']])) {
                        $fieldValue = (float) $fields[$column['field']];
                        $milesValue = $fieldValue;
                    } else {
                        $fieldValue = null;
                    }

                    break;

                default:
                    $fieldValue = $fields[$column['field']];
            }

            if (
                $combineBonus
                && isset($bonusColumn)
                && isset($info[$bonusColumn])
                && $column['field'] === self::MILES_COLUMN
            ) {
                $bonusValue = (float) preg_replace('#[\.\,](\d{3})#ims', '$1', $info[$bonusColumn]);
                $milesValue = $fieldValue += $bonusValue;
            }

            $result[] = $this->buildField(
                $column['name'],
                $column['field'],
                $fieldValue,
                $column['valueType']
            );
        }

        return [$result, $milesValue];
    }

    private function buildField($column, $field, $value, $valueType, $type = self::FIELD_TYPE_STRING, $multiplier = null, $color = null)
    {
        return [
            'column' => $column,
            'field' => $field,
            'value' => $value,
            'valueType' => $valueType,
            'type' => $type,
            'multiplier' => $multiplier,
            'color' => $color,
        ];
    }

    private function sortFields($fields, $columns)
    {
        $result = [];

        foreach ($columns as $column) {
            foreach ($fields as $field) {
                if ($field['column'] === $column['name']) {
                    if (array_key_exists('isOffer', $column)) {
                        $field['isOffer'] = $column['isOffer'];
                    }
                    $result[] = $field;

                    continue 2;
                }
            }
        }

        return $result;
    }

    private function getAccountHistory(Account $account, $limit = null, $descriptionFilter = null, $postingDate = null, $fetchAll = true): iterable
    {
        $andPostingDate = !empty($postingDate) ? "AND DATE_FORMAT(h.PostingDate, \"%Y-%m-%d\") <= \"$postingDate\"" : "";
        $andDescriptionFilter = !empty($descriptionFilter) ? "AND h.Description LIKE \"%{$descriptionFilter}%\"" : "";
        $andLimit = !empty($limit) ? "LIMIT {$limit}" : "";

        $sql = "
            SELECT 
                h.*,
                c.Code AS currency,
                DATE_FORMAT(h.PostingDate, \"%Y-%m-%d\") as postingDate,
                httl.TripID
            FROM 
                AccountHistory h
            LEFT JOIN Currency c ON h.CurrencyID = c.CurrencyID
            LEFT OUTER JOIN HistoryToTripLink httl on h.UUID = httl.HistoryID
            WHERE h.AccountID = ?
            AND h.SubAccountID IS NULL
            $andPostingDate
            $andDescriptionFilter
            ORDER BY h.PostingDate DESC, h.Position ASC
            $andLimit
        ";

        if ($fetchAll) {
            return $this->replica->executeQuery($sql, [$account->getId()], [\PDO::PARAM_INT])->fetchAllAssociative();
        }

        return stmtAssoc($this->em->getConnection()->executeQuery($sql, [$account->getId()], [\PDO::PARAM_INT]));
    }

    private function getSubAccountHistory(Subaccount $subAccount, $limit = null, $descriptionFilter = null, $postingDate = null, $fetchAll = true): iterable
    {
        $andPostingDate = !empty($postingDate) ? "AND DATE_FORMAT(h.PostingDate, \"%Y-%m-%d\") <= \"$postingDate\"" : "";
        $andDescriptionFilter = !empty($descriptionFilter) ? "AND h.Description LIKE \"%{$descriptionFilter}%\"" : "";
        $andLimit = !empty($limit) ? "LIMIT {$limit}" : "";

        $sql = "
            SELECT
                h.*, c.Code AS currency, DATE_FORMAT(h.PostingDate, \"%Y-%m-%d\") as postingDate, 
                h.MerchantID as merchantId, m.Name as merchantName, cc.Name as cardName, 
                h.Amount as amount, round(h.Miles, 2) as miles, sa.DisplayName as displayName,
                sa.CreditCardID as creditCardId,
                a.ProviderID
            FROM
                AccountHistory h USE INDEX(HistoryDataIndex)
            JOIn Account a on h.AccountID = a.AccountID
            LEFT JOIN Currency c ON h.CurrencyID = c.CurrencyID
            LEFT JOIN Merchant m ON h.MerchantID = m.MerchantID
            LEFT JOIN SubAccount sa ON h.SubAccountID = sa.SubAccountID 
            LEFT JOIN CreditCard cc ON sa.CreditCardID = cc.CreditCardID
            WHERE
                h.AccountID = ?
                AND h.SubAccountID = ?
                $andPostingDate
                $andDescriptionFilter
            ORDER BY
                h.PostingDate DESC, h.Position ASC
            $andLimit
        ";

        if ($fetchAll) {
            return $this->replica->executeQuery(
                $sql,
                [$subAccount->getAccountid()->getId(), $subAccount->getId()],
                [\PDO::PARAM_INT, \PDO::PARAM_INT]
            )->fetchAllAssociative();
        }

        return stmtAssoc($this->em->getConnection()->executeQuery(
            $sql,
            [$subAccount->getAccountid()->getId(), $subAccount->getId()],
            [\PDO::PARAM_INT, \PDO::PARAM_INT]
        ));
    }

    private function buildHistoryColumns(ProviderInfoResponse $info, bool $isEp = false): ?array
    {
        $columnsNotPrepared = $info->getHistorycolumns();

        if (!is_array($columnsNotPrepared) || empty($columnsNotPrepared)) {
            return null;
        }

        $columns = [];
        $columnsType = [];

        /** @var HistoryColumn $columnNotPrepared */
        foreach ($columnsNotPrepared as $columnNotPrepared) {
            if ($columnNotPrepared->isHidden()) {
                continue;
            }

            // refs #13946 убираем колонку, добавляем валюту через LocalizeService::formatCurrency()
            if ($columnNotPrepared->getKind() === 'Currency') {
                continue;
            }

            $columns[$columnNotPrepared->getName()] = $columnNotPrepared->getKind();
            $columnsType[$columnNotPrepared->getName()] = $columnNotPrepared->getType();
        }

        $sortedColumns = [];
        $amountExists = false;

        foreach (['PostingDate', 'Description', self::AMOUNT_COLUMN, self::MILES_COLUMN] as $item) {
            $key = array_search($item, $columns);

            if ($key !== false) {
                $sortedColumns[] = $this->buildColumn(
                    $key, $item, $columnsType[$key], in_array($item, [self::MILES_COLUMN, self::AMOUNT_COLUMN])
                );
                unset($columns[$key]);

                if ($item === self::AMOUNT_COLUMN) {
                    $amountExists = true;
                }
            }
        }

        foreach ($columns as $columnName => $columnKind) {
            $offset = $amountExists ? -2 : -1;
            array_splice($sortedColumns, $offset, 0, [
                $this->buildColumn(
                    $columnName,
                    $columnKind,
                    $columnsType[$columnName],
                    in_array(
                        $columnsType[$columnName], [self::VALUE_TYPE_DECIMAL, self::VALUE_TYPE_INTEGER]
                    )
                ),
            ]);
        }

        if ($isEp) {
            $col = $this->buildColumn(self::EARNING_POTENTIAL_COLUMN, self::EARNING_POTENTIAL_COLUMN, self::EARNING_POTENTIAL_VALUE_TYPE, true);
            $col['isOffer'] = true;
            $sortedColumns[] = $col;
        }

        return $sortedColumns;
    }

    private function buildColumn($name, $field, $valueType, $isBalance = false)
    {
        return [
            'name' => $name,
            'field' => $field,
            'valueType' => $valueType,
            'isBalance' => $isBalance,
        ];
    }

    private function getBonusColumnIndex(array $columns): ?int
    {
        for ($i = 0; $i < count($columns); $i++) {
            if ($columns[$i]['field'] === self::BONUS_COLUMN) {
                return $i;
            }
        }

        return null;
    }

    private function prepareSubAccountData(
        Account $account,
        array &$fields,
        array $row,
        Context $cacheContext,
        HistoryQuery $query
    ) {
        foreach ($fields as &$field) {
            if ($field['field'] === self::MILES_COLUMN) {
                $milesField = &$field;
            }
        }

        // $pointValueItem = $this->mileValueService->getMileValueViaCreditCardId((int) $row['creditCardId'], $cacheContext);
        $pointValueItem = $this->mileValueCards->getCardMileValueCost((int) $row['creditCardId']);
        $pointValue = $pointValueItem->getPrimaryValue();
        $multiplier = null;

        if ($row['miles'] !== null && $row['amount'] !== null && $row['miles'] >= 0) {
            $multiplier = (float) MultiplierService::calculate($row['amount'], $row['miles'], $row['ProviderID']);
        }

        $shiftedMiles = round((float) $row['amount'] * $multiplier);
        $shiftedMilesValue = round($pointValue * 0.01 * $shiftedMiles, 2);
        $offerData = $this->analyser->detectPotential(
            $account->getUser(),
            array_merge($row, ['milesValue' => $shiftedMilesValue]),
            $row['postingDate'],
            $query->getOfferCards(),
            $cacheContext
        );
        $milesField['multiplier'] = $multiplier;

        $epType = self::FIELD_TYPE_THUMB_UP;

        if ((float) $row['miles'] <= 0 || (float) $row['amount'] <= 0) {
            $epType = self::FIELD_TYPE_EMPTY;
        }

        if (isset($offerData['potentialValue']) && $offerData['potentialValue'] > ($shiftedMilesValue * 1.02)) {
            $epType = self::FIELD_TYPE_OFFER;
        }
        $fields[] = $this->buildField(
            self::EARNING_POTENTIAL_COLUMN,
            self::EARNING_POTENTIAL_COLUMN,
            $offerData['potentialMiles'],
            self::EARNING_POTENTIAL_VALUE_TYPE,
            $epType,
            $offerData['potential'] ?? null,
            $offerData['earningPotentialColor'] ?? null
        );

        /* redefine Category field */
        foreach ($fields as &$field) {
            if (empty($row['merchantId'])) {
                break;
            }

            if ($field['field'] !== self::CATEGORY_COLUMN) {
                continue;
            }

            $merchant = $this->em->getRepository(Merchant::class)->find($row['merchantId']);

            if (
                $merchant->getShoppingcategory() instanceof ShoppingCategory
                && ($group = $merchant->getShoppingcategory()->getGroup()) instanceof ShoppingCategoryGroup
            ) {
                $field['value'] = $group->getName();

                break;
            }
        }

        return [$shiftedMilesValue, $offerData];
    }

    private function getHistoryRow(
        array $row,
        array $fields,
        array $columns,
        float $milesValue,
        ?float $shiftedMilesValue,
        ?array $offerData
    ): array {
        return [
            'uuid' => $row['UUID'],
            'cells' => $this->sortFields($fields, $columns),
            'currency' => $row['currency'] ?? null,
            'merchantName' => isset($row['merchantName']) ? str_replace('#', '', $row['merchantName']) : null,
            'isPositiveTransaction' => $milesValue >= 0,
            'tripId' => $row['TripID'] ?? null,
            'pointsValue' => $shiftedMilesValue ?? null,
            'potentialPointsValue' => $offerData['potentialValue'] ?? null,
        ];
    }
}
