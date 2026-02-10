<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\Translation\TranslatorInterface;

class TransferTimes
{
    // time in seconds, which is defined as 'Immediate'
    public const IMMEDIATE_TIME = 60 * 15;

    // mysql interval format DATE_SUB()
    public const OPERATION_INTERVAL = 'INTERVAL 18 MONTH';

    // order of providers in the table for users. Order is important
    public const PROVIDERS_ORDER = [
        Provider::AMEX_ID,
        Provider::CAPITAL_ONE_ID,
        Provider::CHASE_ID,
        Provider::CITI_ID,
        Provider::MARRIOTT_ID,
    ];

    private Connection $db;

    private LocalizeService $localizer;

    private TranslatorInterface $translator;

    private DateTimeIntervalFormatter $intervalFormatter;

    private BlogPostInterface $blogPost;

    public function __construct(
        Connection $db,
        LocalizeService $localizer,
        TranslatorInterface $translator,
        DateTimeIntervalFormatter $intervalFormatter,
        BlogPostInterface $blogPost
    ) {
        $this->db = $db;
        $this->localizer = $localizer;
        $this->translator = $translator;
        $this->intervalFormatter = $intervalFormatter;
        $this->blogPost = $blogPost;
    }

    /**
     * If PointSource is correct value then return true.
     */
    public function checkPointSource($pointSource): bool
    {
        if (!empty($pointSource) && in_array($pointSource, BalanceWatch::POINTS_SOURCE_VALUES)) {
            return true;
        }

        return false;
    }

    /**
     * Get rows from TransferStat/PurchaseStat.
     *
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getData(
        int $pointSource,
        ?int $onlyProviderIdTo = null,
        ?int $onlyProviderIdFrom = null,
        array $options = []
    ): array {
        if ($this->checkPointSource($pointSource) !== true) {
            return [
                'data' => [],
                'error' => 'unknown points source',
            ];
        }

        $isExpectBonusEnd = array_key_exists('expectFields', $options)
            && in_array('BonusEndDate', $options['expectFields'], true);

        $isTransfer = (BalanceWatch::POINTS_SOURCE_TRANSFER === $pointSource);
        $isPurchase = (BalanceWatch::POINTS_SOURCE_PURCHASE === $pointSource);

        $orderClause = "CASE " . ($isTransfer ? "s.SourceProviderID" : 's.ProviderID') . "\n";

        foreach (self::PROVIDERS_ORDER as $k => $value) {
            $orderClause .= "    WHEN " . $value . " THEN " . $k . "\n";
        }
        $orderClause .= "    ELSE " . (count(self::PROVIDERS_ORDER) + 10) . "\n";
        $orderClause .= "END";

        if ($isTransfer) {
            $usOnly = isset($options['isUSOnly'])
                ? " AND (
                       s.SourceProgramRegion IS NULL
                    OR s.SourceProgramRegion = ''
                    OR s.SourceProgramRegion = 'US'
                    OR s.SourceProgramRegion = 'USA'
                    OR s.SourceProgramRegion = 'United States'
                )
                AND (
                       s.TargetProgramRegion IS NULL
                    OR s.TargetProgramRegion = ''
                    OR s.TargetProgramRegion = 'US'
                    OR s.TargetProgramRegion = 'USA'
                    OR s.TargetProgramRegion = 'United States'
                )"
                : '';

            $query = $this->db->prepare("
                SELECT
                    pfrom.ProviderID as ProviderIDFrom,
                    pfrom.DisplayName as ProviderFromName,
                    pfrom.BlogIdsMilesTransfers as BlogIdsMilesTransfersFrom,
                    p.DisplayName as ProviderName, p.ProviderID, p.BlogIdsMilesTransfers,
                    pfrom.Login2Required as fromLogin2Required, pfrom.Login2AsCountry as fromLogin2AsCountry, pfrom.Login2Caption as fromLogin2Caption, p.Login2Required, p.Login2AsCountry, p.Login2Caption,
                    s.CalcDuration as TransferDuration,
                    s.TransactionCount as OperationsCount,
                    s.MinDuration, s.MaxDuration,
                    " . $orderClause . " as ProvOrderPos,
                    s.SourceRate, s.TargetRate, s.CustomMessage, s.MinimumTransfer,
                    s.BonusStartDate, s.BonusEndDate, s.BonusPercentage,
                    s.SourceProgramRegion, s.TargetProgramRegion,
                    s.SourceProgramRegion, s.TargetProgramRegion,
                    s.SourceProviderID, s.TargetProviderID
                FROM TransferStat s
                LEFT OUTER JOIN Provider pfrom on pfrom.ProviderID = s.SourceProviderID
                LEFT OUTER JOIN Provider p on p.ProviderID = s.TargetProviderID
                WHERE 1
                    " . ($onlyProviderIdTo ? ' AND s.TargetProviderID = ' . $onlyProviderIdTo : '') . "
                    " . ($onlyProviderIdFrom ? ' AND s.SourceProviderID = ' . $onlyProviderIdFrom : '') . "
                    " . $usOnly . "
                ORDER BY ProvOrderPos, ProviderFromName, ProviderName
            ");
        } else {
            $query = $this->db->prepare("
                SELECT
                    p.DisplayName as ProviderName,
                    p.ProviderID,
                    p.BlogIdsMilesPurchase,
                    s.CalcDuration as TransferDuration,
                    s.TransactionCount as OperationsCount,
                    s.MinDuration,
                    s.MaxDuration,
                    " . $orderClause . " as ProvOrderPos,
                    s.BonusStartDate, s.BonusEndDate,
                    s.BonusDescription, s.DetailedText, s.DetailedLink, s.OfferLink
                FROM PurchaseStat s
                LEFT OUTER JOIN Provider p on p.ProviderID = s.ProviderID
                WHERE 1
                    " . ($onlyProviderIdTo ? ' AND s.TargetProviderID = ' . $onlyProviderIdTo : '') . "
                    " . ($onlyProviderIdFrom ? ' AND s.SourceProviderID = ' . $onlyProviderIdFrom : '') . "
                ORDER BY p.Accounts DESC, ProviderName ASC
            ");
        }

        $data = $query->executeQuery()->fetchAllAssociative();

        $providersBlogpostIndex = [];
        $providersBlogpost = $isTransfer
            ? $this->assignProviderBlogposts($data, ['ProviderIDFrom' => 'BlogIdsMilesTransfersFrom', 'ProviderID' => 'BlogIdsMilesTransfers'])
            : $this->assignProviderBlogposts($data, ['ProviderID' => 'BlogIdsMilesPurchase']);
        $blogposts = empty($providersBlogpost) ? [] : $this->blogPost->fetchPostById(array_merge(...$providersBlogpost));

        $now = time();

        foreach ($data as $key => $v) {
            $data[$key]['isUnknown'] = false;
            $data[$key]['Bonus'] = '';

            $isBonusTime = false;

            if (!empty($v['BonusEndDate'])) {
                // offset 5hours for EST time (refs 19719 #note-3)
                $bonusEndDate = (new \DateTime($v['BonusEndDate'], new \DateTimeZone('EST')))->getTimestamp();

                if ($now < $bonusEndDate) {
                    $isBonusTime = true;
                }
            }

            // Bonus - Transfer
            if ($isTransfer) {
                $bonus = 100;

                if ((!empty($v['BonusPercentage']) || !empty($v['CustomMessage']))
                    && (empty($v['BonusStartDate']) || (!empty($v['BonusStartDate']) && strtotime($v['BonusStartDate']) < strtotime("now")))
                    && (empty($v['BonusEndDate']) || $isBonusTime)
                ) {
                    $bonus += $v['BonusPercentage'];
                    $data[$key]['Bonus'] = $this->translator->trans(/** @Desc("%bonus%% bonus") */ 'percent_bonus', ['%bonus%' => $v['BonusPercentage']]);

                    if (!empty($v['CustomMessage'])) {
                        $data[$key]['Bonus'] = $v['CustomMessage'];
                    } elseif (!empty($v['BonusEndDate'])) {
                        $data[$key]['Bonus'] .= ' (' . $this->translator->trans(
                            'bonus_ends',
                            ['%endDate%' => $this->localizer->formatDate(new \DateTime($v['BonusEndDate']))]
                        ) . ')';
                    }
                }
                unset($data[$key]['BonusStartDate'], $data[$key]['BonusPercentage']);

                if (!$isExpectBonusEnd) {
                    unset($data[$key]['BonusEndDate']);
                }

                // ratio
                $data[$key]['TransferRatio'] = (!empty($v['SourceRate']) && !empty($v['TargetRate']))
                    ? $this->localizer->formatNumber($v['SourceRate']) . ':' . $this->localizer->formatNumber((int) $v['TargetRate'] * $bonus / 100)
                    : '';

                if (100 !== $bonus) {
                    $data[$key]['TargetRate'] = (int) ($v['TargetRate'] * $bonus / 100);
                }

                if (!empty($data[$key]['MinimumTransfer'])) {
                    $data[$key]['MinimumTransfer'] = $this->localizer->formatNumber($data[$key]['MinimumTransfer']);
                }

                if (empty($data[$key]['SourceProgramRegion'])
                    && (bool) $data[$key]['fromLogin2Required']
                    && (
                        (bool) $data[$key]['fromLogin2AsCountry']
                        || 'Country' == $data[$key]['fromLogin2Caption']
                    )
                ) {
                    $data[$key]['SourceProgramRegion'] = 'United States';
                }

                if (empty($data[$key]['TargetProgramRegion'])
                    && (bool) $data[$key]['Login2Required']
                    && (
                        (bool) $data[$key]['Login2AsCountry']
                        || 'Country' == $data[$key]['Login2Caption']
                    )
                ) {
                    $data[$key]['TargetProgramRegion'] = 'United States';
                }

                unset(
                    $data[$key]['fromLogin2Required'], $data[$key]['fromLogin2AsCountry'], $data[$key]['fromLogin2Caption'],
                    $data[$key]['Login2Required'], $data[$key]['Login2AsCountry'], $data[$key]['Login2Caption']
                );

                if (in_array($data[$key]['SourceProgramRegion'], ['US', 'USA'])) {
                    $data[$key]['SourceProgramRegion'] = 'United States';
                }

                if (in_array($data[$key]['TargetProgramRegion'], ['US', 'USA'])) {
                    $data[$key]['TargetProgramRegion'] = 'United States';
                }
            }

            // Bonus - Purchase
            if ($isPurchase) {
                if (
                    !empty($v['BonusDescription'])
                    && (empty($v['BonusStartDate']) || (!empty($v['BonusStartDate']) && strtotime($v['BonusStartDate']) < $now))
                    && (empty($v['BonusEndDate']) || $isBonusTime)
                ) {
                    $data[$key]['Bonus'] = $v['BonusDescription'];

                    if (!empty($v['BonusEndDate']) && $now < strtotime($v['BonusEndDate'])) {
                        $data[$key]['Bonus'] .= ' (' . $this->translator->trans(/** @Desc("deal ends %endDate%") */ 'bonus_ends',
                            ['%endDate%' => $this->localizer->formatDate(new \DateTime($v['BonusEndDate']))]
                        ) . ')';
                    }

                    $data[$key]['Bonus'] = $this->processBonusText($v, $data[$key]['Bonus']);
                    $data[$key]['HowPurchase'] = $this->getPurchaseText($v);
                }
                unset($data[$key]['BonusStartDate'], $data[$key]['BonusDescription'], $data[$key]['BonusEndDate']);
            }

            if ($v['MaxDuration'] !== null && (float) $v['MaxDuration'] === 0.0) {
                $data[$key]['TransferDuration'] = $this->translator->trans('immediate');
            } elseif ($v['TransferDuration'] > 0) {
                $data[$key]['TransferDuration'] = ($v['TransferDuration'] < self::IMMEDIATE_TIME)
                    ? $this->translator->trans(/** @Desc("Immediate") */ 'immediate')
                    : $this->intervalFormatter->formatDurationViaInterval(
                        \DateInterval::createFromDateString(sprintf('%d second', $v['TransferDuration'])),
                        false,
                        true
                    );
                $data[$key]['countRowsTip'] = $this->translator->trans(
                    'transaction_based_on_calc_value',
                    ['%count%' => $v['OperationsCount'] ?? 0]
                );
            } elseif ($v['MaxDuration'] !== null || $v['MinDuration'] !== null) {
                if ($v['MaxDuration'] == null && $v['MinDuration'] !== null) {
                    $v['MaxDuration'] = $v['MinDuration'];
                    $v['MinDuration'] = null;
                }

                switch (true) {
                    case $v['MaxDuration'] * 60 * 60 < self::IMMEDIATE_TIME:
                        $data[$key]['TransferDuration'] = $this->translator->trans('immediate');

                        break;

                    case $v['MinDuration'] == $v['MaxDuration'] || empty((float) $v['MinDuration']):
                        $data[$key]['TransferDuration'] = $this->intervalFormatter->formatDurationViaInterval(
                            \DateInterval::createFromDateString(sprintf('%d second', round($v['MaxDuration'] * 60 * 60))),
                            false,
                            true
                        );

                        break;

                    default:
                        $data[$key]['TransferDuration'] =
                            $this->intervalFormatter->formatDurationViaInterval(
                                \DateInterval::createFromDateString(sprintf('%d second', round($v['MinDuration'] * 60 * 60))),
                                false,
                                true
                            ) . ' - ' .
                            $this->intervalFormatter->formatDurationViaInterval(
                                \DateInterval::createFromDateString(sprintf('%d second', round($v['MaxDuration'] * 60 * 60))),
                                false,
                                true
                            );

                        break;
                }
                $data[$key]['countRowsTip'] = $this->translator->trans(/** @Desc("manually set by AwardWallet") */
                    'manually_set_by_aw'
                );
            } else {
                $data[$key]['TransferDuration'] = $this->translator->trans('unknown');
                $data[$key]['countRowsTip'] = $this->translator->trans('unknown');
                $data[$key]['isUnknown'] = true;
            }

            $linkArg = $isTransfer
                ? ['cid' => 'transfer-times-transfer', 'mid' => 'web']
                : ['cid' => 'transfer-times-buy', 'mid' => 'web'];

            if ($isTransfer) {
                $providerId = (int) $data[$key]['ProviderIDFrom'];

                if (array_key_exists($providerId, $providersBlogpost)) {
                    $index = $providersBlogpostIndex[$providerId] ?? 0;
                    $index = array_key_exists($index, $providersBlogpost[$providerId]) ? $index : 0;

                    if (null !== $blogposts && array_key_exists($providersBlogpost[$providerId][$index], $blogposts)) {
                        $data[$key]['ProviderLinkFrom'] = StringHandler::replaceVarInLink(
                            $blogposts[$providersBlogpost[$providerId][$index]]['postURL'],
                            $linkArg,
                            true
                        );
                    }
                    $providersBlogpostIndex[$providerId] = ++$index;
                }
            }

            $providerId = (int) $data[$key]['ProviderID'];

            if (array_key_exists($providerId, $providersBlogpost)) {
                $index = $providersBlogpostIndex[$providerId] ?? 0;
                $index = array_key_exists($index, $providersBlogpost[$providerId]) ? $index : 0;

                if (null !== $blogposts && array_key_exists($providersBlogpost[$providerId][$index], $blogposts)) {
                    $data[$key]['ProviderLinkTo'] = StringHandler::replaceVarInLink(
                        $blogposts[$providersBlogpost[$providerId][$index]]['postURL'],
                        $linkArg,
                        true
                    );
                }
                $providersBlogpostIndex[$providerId] = ++$index;
            }

            unset(
                // $data[$key]['ProviderIDFrom'],
                $data[$key]['BlogIdsMilesTransfersFrom'],
                $data[$key]['BlogIdsMilesTransfers'],
            );
        }

        return [
            'data' => $data,
        ];
    }

    /**
     * Updates all tables associated with the Transfer/Purchase Times.
     *
     * @return array ['status' => bool, 'message' => string]
     */
    public function updateTransferTimes(): array
    {
        $result = ['status' => null, 'message' => []];

        $r = $this->updateBWStatuses();

        if (!empty($r)) {
            $result['status'] = 'Failed';
            $result['message'][] = $r;
        }

        $r = $this->updateStats(BalanceWatch::POINTS_SOURCE_PURCHASE);

        if (!empty($r)) {
            $result['status'] = 'Failed';
            $result['message'][] = $r;
        }

        $r = $this->updateStats(BalanceWatch::POINTS_SOURCE_TRANSFER);

        if (!empty($r)) {
            $result['status'] = 'Failed';
            $result['message'][] = $r;
        }

        $r = $this->updateBWStatusesStdev();

        if (!empty($r)) {
            $result['status'] = 'Failed';
            $result['message'][] = $r;
        }

        return ['status' => $result['status'] ?? 'Success', 'message' => implode("\n", $result['message'])];
    }

    public function processBonusText(array $row, string $bonus): string
    {
        if (!empty($row['DetailedText']) && !empty($row['DetailedLink'])
            && filter_var($row['DetailedLink'], FILTER_VALIDATE_URL)
        ) {
            $bonus = '<a href="' . $row['DetailedLink'] . '" target="_blank">' . $row['DetailedText'] . '</a> '
                . $bonus;
        }

        return $bonus;
    }

    public function getPurchaseText(array $row): string
    {
        if (!empty($row['OfferLink']) && filter_var($row['OfferLink'], FILTER_VALIDATE_URL)) {
            return '<a href="' . $row['OfferLink'] . '" target="_blank">'
                . $this->translator->trans(/** @Desc("Buy Now") */ 'buy-now')
                . '</a>';
        }

        return '';
    }

    private function assignProviderBlogposts(array $data, array $providersBlogKey): array
    {
        $assign = [];

        foreach ($providersBlogKey as $providerIdKey => $blogpostIdKey) {
            foreach ($data as $item) {
                if (empty($item[$blogpostIdKey])) {
                    continue;
                }
                $providerId = (int) $item[$providerIdKey];

                if (!array_key_exists($providerId, $assign)) {
                    $assign[$providerId] = [];
                }
                $blogpostIds = explode(',', $item[$blogpostIdKey]);
                $blogpostIds = array_map('trim', $blogpostIds);

                $assign[$providerId] = array_merge($assign[$providerId], $blogpostIds);
                $assign[$providerId] = array_unique($assign[$providerId]);
            }
        }

        return $assign;
    }

    /**
     * Updates rows TransferStat/PurchaseStat.
     *
     * @return string Message (empty if successful)
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateStats(int $pointSource): string
    {
        if ($this->checkPointSource($pointSource) !== true) {
            return 'Error updating transfer/purchase: unknown points source';
        }

        $isTransfer = ($pointSource == BalanceWatch::POINTS_SOURCE_TRANSFER);

        $this->db->beginTransaction();

        try {
            // delete old data
            $query = $this->db->executeUpdate("
                update " . ($isTransfer ? 'TransferStat' : 'PurchaseStat') . "
                  set CalcDuration = NULL, TransactionCount = NULL, TimeDeviation = NULL
            ");

            // Insert or update stats
            // average values are calculated based on the last 5 most recent results for all time
            $query = $this->db->executeUpdate("
                insert into " . ($isTransfer ? 'TransferStat' : 'PurchaseStat') . " (
                    " . ($isTransfer ? 'SourceProviderID, SourceProgramRegion, TargetProgramRegion, TargetProviderID' : 'ProviderID') . ",
                    CalcDuration, TransactionCount, TimeDeviation)
                SELECT *
                FROM (
                    SELECT
                        " . ($isTransfer ? 'SourceProviderID, IF(SourceProgramRegion IS NULL, \'\', SourceProgramRegion), IF(TargetProgramRegion IS NULL, \'\', TargetProgramRegion), ' : '') . "
                        TargetProviderID,
                        avg(timeDiff) as CalcDuration,
                        count(BalanceWatchID) as TransactionCount,
                        STD(timeDiff) as TimeDeviation
                    FROM (
                        SELECT 
                            *,
                            @num := if(@TargetProviderID = sa.TargetProviderID " . ($isTransfer ? ' and @SourceProviderID = sa.SourceProviderID' : '') . ", @num + 1, 1) as rowNumber,
                            " . ($isTransfer ? '@SourceProviderID := sa.SourceProviderID, ' : '') . "
                            @TargetProviderID := sa.TargetProviderID
                        FROM (
                            SELECT
                                " . ($isTransfer ? 'b.TransferFromProviderID as SourceProviderID, b.SourceProgramRegion, b.TargetProgramRegion, ' : '') . "
                                a.ProviderID as TargetProviderID,
                                b.BalanceWatchID,
                                unix_timestamp(b.StopDate)-unix_timestamp(b.TransferRequestDate) timeDiff
                            FROM BalanceWatch b
                                JOIN Account a on a.AccountID = b.AccountID
                            WHERE
                                " . $this->getTransferCondition('b', 'a', false) . "
                                and b.PointsSource = " . $pointSource . "
                                AND b.Status <> '" . BalanceWatch::STATUS_ERROR . "'
                                AND b.Status <> '" . BalanceWatch::STATUS_REVIEW . "'
                                ORDER BY a.ProviderID, TransferRequestDate DESC
                        ) sa
                    ) sb
                    where rowNumber < 5
                    GROUP BY " . ($isTransfer ? 'SourceProviderID, ' : '') . "TargetProviderID " . ($isTransfer ? ', SourceProgramRegion, TargetProgramRegion' : '') . "
                ) as ss
                ON DUPLICATE KEY UPDATE
                    CalcDuration = ss.CalcDuration, TransactionCount = ss.TransactionCount, TimeDeviation = ss.TimeDeviation
            ");

            // average values calculated based on 3 or more results within the last 18 months(period = self::OPERATION_INTERVAL)
            $operationCount = 3;
            $query = $this->db->executeUpdate("
                insert into " . ($isTransfer ? 'TransferStat' : 'PurchaseStat') . " (
                    " . ($isTransfer ? 'SourceProviderID, SourceProgramRegion, TargetProgramRegion, TargetProviderID' : 'ProviderID') . ",
                    CalcDuration, TransactionCount, TimeDeviation)
                SELECT *
                FROM (
                    SELECT
                        " . ($isTransfer ? 'b.TransferFromProviderID as SourceProviderID, IF(SourceProgramRegion IS NULL, \'\', SourceProgramRegion), IF(TargetProgramRegion IS NULL, \'\', TargetProgramRegion), ' : '') . "
                        a.ProviderID as TargetProviderID,
                        avg(unix_timestamp(b.StopDate)-unix_timestamp(b.TransferRequestDate)) as CalcDuration,
                        count(b.BalanceWatchID) as TransactionCount,
                        STD(unix_timestamp(b.StopDate)-unix_timestamp(b.TransferRequestDate)) as TimeDeviation
                    FROM BalanceWatch b
                        JOIN Account a on a.AccountID = b.AccountID
                    WHERE
                        " . $this->getTransferCondition('b', 'a') . "
                        and b.PointsSource = " . $pointSource . "
                        AND b.Status <> '" . BalanceWatch::STATUS_ERROR . "'
                        AND b.Status <> '" . BalanceWatch::STATUS_REVIEW . "'
                    GROUP BY " . ($isTransfer ? 'b.TransferFromProviderID, ' : '') . "a.ProviderID" . ($isTransfer ? ', SourceProgramRegion, TargetProgramRegion' : '') . "
                    HAVING count(b.BalanceWatchID) >= $operationCount
                ) as ss
                ON DUPLICATE KEY UPDATE
                    CalcDuration = ss.CalcDuration, TransactionCount = ss.TransactionCount, TimeDeviation = ss.TimeDeviation
            ");

            $this->db->commit();

            return '';
        } catch (\DBALException $e) {
            $this->db->rollBack();

            return 'Error updating ' . ($isTransfer ? 'transfer' : 'purchase') . ' stat: ' . $e->getMessage();
        }
    }

    /**
     * Get query for update status when transfer/purchase time more than 1 standard deviation from the mean.
     */
    private function getBWStatusQueryDeviation(int $pointSource): string
    {
        if ($this->checkPointSource($pointSource) !== true) {
            return ['error' => 'Error updating transfer/purchase BW Status Query Deviation: unknown points source'];
        }

        $isTransfer = ($pointSource == BalanceWatch::POINTS_SOURCE_TRANSFER);

        return "
                update 
                    BalanceWatch b
                    left join Account a on a.AccountID = b.AccountID
                    join (
                        SELECT
                            " . ($isTransfer ? 'TransferStatID' : 'PurchaseStatID') . ",
                            " . ($isTransfer ? 'SourceProviderID, TargetProviderID' : 'ProviderID') . ",
                            CalcDuration,
                            TimeDeviation
                        FROM " . ($isTransfer ? 'TransferStat' : 'PurchaseStat') . "
                    ) ss on " . ($isTransfer ? 'b.TransferFromProviderID = ss.SourceProviderID and a.ProviderID = ss.TargetProviderID' : 'a.ProviderID = ss.ProviderID') . "
                SET b.status = '" . BalanceWatch::STATUS_REVIEW . "', 
                    b.Note = concat(if (b.Note is not null, concat(b.Note, '. '), ''), 'deviation: ', ROUND(ss.TimeDeviation/60/60, 2), ', average: ', ROUND(ss.CalcDuration/60/60, 2),', delta: ', ROUND(abs(unix_timestamp(b.StopDate)-unix_timestamp(b.TransferRequestDate) - ss.CalcDuration)/60/60, 2) ) 
                where
                    " . $this->getTransferCondition('b', 'a') . "
                    and b.PointsSource = " . $pointSource . "
                    and b.Status = '" . BalanceWatch::STATUS_NEW . "'
                    and ss." . ($isTransfer ? 'TransferStatID' : 'PurchaseStatID') . " is not null and abs(unix_timestamp(b.StopDate)-unix_timestamp(b.TransferRequestDate) - ss.CalcDuration) > ss.TimeDeviation
            ";
    }

    /**
     * Set statuses for rows in BalanceWatch when transfer/purchase time more than 1 standard deviation from the mean.
     *
     * @return string Message (empty if successful)
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateBWStatusesStdev(): string
    {
        $this->db->beginTransaction();

        try {
            $sql = $this->getBWStatusQueryDeviation(BalanceWatch::POINTS_SOURCE_TRANSFER);

            if (isset($sql['error'])) {
                $this->db->rollBack();

                return $sql['error'];
            }
            $query = $this->db->executeUpdate($sql);

            $sql = $this->getBWStatusQueryDeviation(BalanceWatch::POINTS_SOURCE_PURCHASE);

            if (isset($sql['error'])) {
                $this->db->rollBack();

                return $sql['error'];
            }
            $query = $this->db->executeUpdate($sql);

            $this->db->commit();

            return '';
        } catch (\DBALException $e) {
            $this->db->rollBack();

            return 'Error updating BalanceWatch statuses(use standard deviation): ' . $e->getMessage();
        }
    }

    /**
     * Set statuses for rows in BalanceWatch.
     *
     * @return string Message (empty if successful)
     * @throws \Doctrine\DBAL\ConnectionException
     * @throws \Doctrine\DBAL\DBALException
     */
    private function updateBWStatuses(): string
    {
        $this->db->beginTransaction();

        try {
            // when there is no good status for providers pair
            $query = $this->db->executeUpdate("
                update
                    BalanceWatch b
                    left join Account a on a.AccountID = b.AccountID
                    left join (
                        SELECT distinct
                            bl.TransferFromProviderID,
                            al.ProviderID,
                            bl.PointsSource
                        FROM BalanceWatch bl
                            left JOIN Account al on al.AccountID = bl.AccountID
                        WHERE
                            " . $this->getTransferCondition('bl', 'al') . "
                            AND bl.Status = '" . BalanceWatch::STATUS_GOOD . "'
                    ) bw on ( (b.TransferFromProviderID = bw.TransferFromProviderID) or (b.TransferFromProviderID is null and bw.TransferFromProviderID is null) )
                        and a.ProviderID = bw.ProviderID
                        and b.PointsSource = bw.PointsSource
                SET b.status = '" . BalanceWatch::STATUS_REVIEW . "'
                where
                    " . $this->getTransferCondition('b', 'a') . "
                    and b.Status = '" . BalanceWatch::STATUS_NEW . "'
                    and bw.PointsSource is null
            ");

            // set error status
            $query = $this->db->executeUpdate("
                update 
                    BalanceWatch b
                SET b.status = '" . BalanceWatch::STATUS_ERROR . "'
                where
                    TransferRequestDate > DATE_SUB(NOW(), " . self::OPERATION_INTERVAL . ")
                    and (StopReason = " . BalanceWatch::REASON_TIMEOUT . "
                        or StopReason = " . BalanceWatch::REASON_UPDATE_ERROR . ")
                    and b.Status = '" . BalanceWatch::STATUS_NEW . "'
            ");

            $this->db->commit();

            return '';
        } catch (\DBALException $e) {
            $this->db->rollBack();

            return 'Error updating BalanceWatch statuses: ' . $e->getMessage();
        }
    }

    /**
     * Return conditions for rows from BalanceWatch.
     *
     * @param string $balanceWatchSyn Synonym for table BalanceWatch
     * @param string $accountSyn Synonym for table Account
     */
    private function getTransferCondition(string $balanceWatchSyn = '', string $accountSyn = '', $withTimeLimit = true): string
    {
        $bw = (!empty($balanceWatchSyn)) ? $balanceWatchSyn . '.' : '';
        $ac = (!empty($accountSyn)) ? $accountSyn . '.' : '';
        $result = '';

        if ($withTimeLimit === true) {
            $result = $bw . "TransferRequestDate > DATE_SUB(NOW(), " . self::OPERATION_INTERVAL . ")
            and ";
        }
        $result .= $bw . "StopReason = " . BalanceWatch::REASON_BALANCE_CHANGED . "
            and (" . $bw . "TransferFromProviderID is null or " . $bw . "TransferFromProviderID <> " . Provider::TEST_PROVIDER_ID . ")
            and (unix_timestamp(" . $bw . "CreationDate)-unix_timestamp(" . $bw . "TransferRequestDate)) < 60*60*24
            and " . $ac . "ProviderID <> " . Provider::TEST_PROVIDER_ID . "";

        return $result;
    }
}
