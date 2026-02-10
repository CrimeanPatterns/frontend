<?php

namespace AwardWallet\MainBundle\Globals\AccountInfo;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Region;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\CheckerFactory;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Info
{
    protected EntityManagerInterface $em;

    protected LocalizeService $localizer;

    protected TranslatorInterface $translator;

    protected string $appDir;

    protected array $shoppingCategoryPatterns = [];

    protected array $freedomCard = [];

    private BankTransactionsAnalyser $analyser;

    private CheckerFactory $checkerFactory;

    private GeoLocation $geoLocation;
    private LoggerInterface $logger;

    public function __construct(EntityManager $em,
        LocalizeService $localizer,
        TranslatorInterface $translator,
        \AppKernel $kernel,
        BankTransactionsAnalyser $analyser,
        CheckerFactory $checkerFactory,
        GeoLocation $geoLocation,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->localizer = $localizer;
        $this->translator = $translator;
        $this->appDir = $kernel->getProjectDir();
        $this->analyser = $analyser;
        $this->checkerFactory = $checkerFactory;
        $this->geoLocation = $geoLocation;
        $this->logger = (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
    }

    public function getAccountBalanceChartQuery(int $accountId, int $limit = 15, ?int $subAccountId = null)
    {
        $chartValuesY = [];
        $chartValuesX = [];
        $stm = $this->getBalanceHistoryStatement($accountId, $limit, $subAccountId);

        while ($fields = $stm->fetch(\PDO::FETCH_ASSOC)) {
            $chartValuesY[] = $fields['Balance'];
            $chartValuesX[] = $this->localizer->formatDateTime(new \DateTime('@' . strtotime($fields['UpdateDate'])), 'short', 'none');
        }

        if (sizeof($chartValuesY) > 1) {
            return http_build_query([
                'l' => implode('|', $chartValuesX),
                'd' => implode('|', $chartValuesY),
            ]);
        } else {
            return null;
        }
    }

    /**
     * @param array $data [date1 => balance1, date2 => balance2, ...]
     */
    public function getAccountBalanceChart(array $labels, array $data, float $multiplier = 1)
    {
        $countDataLimit = 10;
        $countData = \count($data);

        if ($countData > $countDataLimit) {
            $data = array_slice($data, $countData - $countDataLimit, $countDataLimit);
            $labels = array_slice($labels, $countData - $countDataLimit, $countDataLimit);
            $countData = $countDataLimit;
        }

        $avgLengthData = array_sum(array_map('strlen', $data)) / $countData;

        $numberFormatterInner = function ($value, bool $alwaysReduce = false) {
            $value = floatval($value);

            if ($alwaysReduce) {
                return $this->localizer->formatLargeNumber($value, 2);
            }

            return $this->localizer->formatNumber($value);
        };
        $numberFormatter = function ($value, bool $alwaysReduce = false) use ($numberFormatterInner) {
            try {
                return $numberFormatterInner($value, $alwaysReduce);
            } catch (\Throwable $throwable) {
                $this->logger->info(
                    'BalanceChart callback error: ' . $throwable->getMessage(),
                    [
                        'balance_chart_value' => $value,
                        'alwaysReduce_value' => $alwaysReduce,
                    ],
                );

                throw $throwable;
            }
        };

        $interpolate = function (int $startValue, int $endValue, int $startInput, int $endInput, int $currentInput): int {
            $fraction = ($currentInput - $startInput) / ($endInput - $startInput);
            $interpolatedValue = $startValue + ($endValue - $startValue) * $fraction;

            return round($interpolatedValue);
        };
        $interpolateByBars = function (
            int $startValue,
            int $endValue,
            bool $addMultiplier = true
        ) use ($interpolate, $countDataLimit, $countData, $multiplier): int {
            $value = $interpolate($startValue, $endValue, 1, $countDataLimit, $countData);

            return $addMultiplier ? $value * $multiplier : $value;
        };

        // Load modules
        \JpGraph\JpGraph::load();
        \JpGraph\JpGraph::module('bar');

        // Create graph
        $graph = new \Graph(700 * $multiplier, 300 * $multiplier);
        $graph->texts = [];
        $graph->y2texts = [];
        $graph->lines = [];
        $graph->y2lines = [];
        $graph->graph_theme = new \SoftyTheme();
        $graph->SetScale('textlin');
        $graph->graph_theme = null;

        // X grid
        $graph->xgrid->Show(false);

        // Y grid
        $graph->ygrid->SetFill(true, '#fff', '#fff');
        $graph->ygrid->SetStyle('dotted');
        $graph->ygrid->SetColor('#bec2cc');

        // X axis
        $graph->xaxis->SetColor('#333333', '#333333'); // Darker color for better visibility
        $graph->xaxis->HideLine();
        $graph->xaxis->HideTicks();
        $graph->xaxis->SetFont(
            FF_DV_SANSSERIF,
            FS_NORMAL,
            $countData > 8 ? $interpolateByBars(11, 8) : 11
        );
        $graph->xaxis->SetTickLabels($labels);
        $graph->xaxis->SetPos('min');
        $graph->xaxis->SetLabelAngle(0);
        $graph->xaxis->SetLabelAlign('center', 'top', 'right');

        // Y axis
        $graph->yaxis->SetColor('#333333', '#333333'); // Darker color for better visibility
        $graph->yaxis->HideLine();
        $graph->yaxis->SetFont(
            FF_DV_SANSSERIF,
            FS_NORMAL,
            $countData > 7 ? $interpolateByBars(11, 7) : 11
        );
        $graph->yaxis->SetLabelFormatCallback(fn ($value) => $numberFormatter($value, true));

        // Create bar plot
        $bplot = new \BarPlot($data);
        $bplot->SetFillGradient('#1b8acf', '#21abf1', GRAD_HOR);
        $bplot->SetColor('#a6aab3');
        $bplot->value->SetColor('#27548A');
        $bplot->value->SetFont(
            FF_DV_SANSSERIF,
            FS_NORMAL,
            $interpolateByBars(10, 7)
        );
        $bplot->value->SetAngle($countData > 7 && $avgLengthData > 7 ? $interpolateByBars(30, 45, false) : 0);
        $bplot->value->SetFormatCallback(fn ($value) => $numberFormatter($value, false));
        $bplot->value->Show();
        $graph->Add($bplot);

        // Set margin
        $graph->img->SetMargin(
            $interpolateByBars(75, 50),
            $interpolateByBars(30, 10),
            $interpolateByBars(70, 55),
            $interpolateByBars(70, 45),
        );

        ob_start();
        $graph->Stroke();

        return ob_get_clean();
    }

    public function getPromotions(Usr $user, Account $account, $limit = 10)
    {
        $sql = "SELECT p.DisplayName,
        	d.DealID,
        	d.Title,
        	IF(dm.Readed IS NULL,0,dm.Readed) MarkRead,
        	IF(dm.Applied IS NULL,0,dm.Applied) MarkApplied,
        	IF(dm.Follow IS NULL,0,dm.Follow) MarkFollow,
        	IF(dm.Manual IS NULL,0,dm.Manual) MarkManual,
            IF(d.BeginDate > DATE_ADD(NOW(), INTERVAL -7 DAY) OR
               d.CreateDate > DATE_ADD(NOW(), INTERVAL -7 DAY), 1, 0) IsNew,
            dr.RegionNames,
            dr.RegionIDs
        FROM Deal d
        	LEFT JOIN Provider p ON p.ProviderID = d.ProviderID
        	LEFT JOIN DealMark dm ON dm.DealID = d.DealID
                AND dm.UserID = :user
            LEFT JOIN (
                SELECT DealID,
                    (SUM(Follow)+SUM(Applied)) TotalFollowApplied,
                    (SUM(Follow)+SUM(Manual)) TotalFollowManual
                FROM DealMark
                GROUP BY DealID
            ) dmTotals ON dmTotals.DealID = d.DealID
            LEFT JOIN (
				SELECT
            		GROUP_CONCAT(DISTINCT Region.Name) as RegionNames,
                	GROUP_CONCAT(DISTINCT Region.RegionID) as RegionIDs,
                    DealID
                FROM DealRegion
                JOIN Region on Region.RegionID = DealRegion.RegionID
                GROUP BY DealID) dr ON dr.DealID = d.DealID
			LEFT JOIN Account a on a.ProviderID = d.ProviderID
            WHERE (NOW() >= d.BeginDate AND NOW() <= d.EndDate)
				AND a.AccountID = :account
			AND
				((
				  IF(dm.Readed IS NULL,0,dm.Readed) = 0
				  AND IF(dm.Manual IS NULL,0,dm.Manual) = 0
                )
				OR
				(
				  IF(dm.Follow IS NULL,0,dm.Follow) = 1
				))
        ORDER BY
        	isNew DESC,
        	dmTotals.TotalFollowManual DESC,
            d.TimesClicked DESC,
            d.CreateDate DESC,
            p.DisplayName
		limit " . $limit;
        $sth = $this->em->getConnection()->prepare($sql);
        $sth->execute([':user' => $user->getUserid(), ':account' => $account->getAccountid()]);
        $result = [];
        $dealRegions = [];

        while ($fields = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $dealRegions[$fields['DealID']] = empty($fields['RegionIDs']) ? null : explode(',', $fields['RegionIDs']);
            $result[] = [
                'id' => $fields['DealID'],
                'title' => html_entity_decode($fields['Title']),
                'region' => empty($fields['RegionNames']) ? null : $fields['RegionNames'],
                'isNew' => $fields['MarkRead'] == "0" && $fields['MarkManual'] == "0",
                'markFollow' => (bool) $fields['MarkFollow'],
                'markApplied' => (bool) $fields['MarkApplied'],
            ];
        }

        if (!sizeof($result)) {
            return null;
        }

        $parentRegions = [];
        $currentRegion = $this->getCountryIdByIp($_SERVER['REMOTE_ADDR']);
        $id = function ($promo) use ($currentRegion, $dealRegions, $parentRegions) {
            if (!is_array($dealRegions[$promo['id']]) || !sizeof($dealRegions[$promo['id']])) {
                return 1;
            }

            if (in_array($currentRegion, $dealRegions[$promo['id']])) {
                return 0;
            }
            $parents = [];

            if (isset($parentRegions[$promo['id']])) {
                $parents = $parentRegions[$promo['id']];
            } else {
                foreach ($dealRegions[$promo['id']] as $regionId) {
                    getAllParentRegions($regionId, $parents);
                }
                $parentRegions[$promo['id']] = $parents;
            }

            if (in_array($currentRegion, $parents)) {
                return 0;
            }

            return 2;
        };
        usort($result, function ($a, $b) use ($id) {
            $idA = $id($a);
            $idB = $id($b);

            if ($a['markFollow'] == $b['markFollow']) {
                if ($idA == $idB) {
                    return 0;
                }

                return ($a < $b) ? -1 : 1;
            }

            return ($a['markFollow'] < $b['markFollow']) ? 1 : -1;
        });

        return $result;
    }

    public function getAccountHistoryColumns(Account $account)
    {
        $provider = $account->getProviderid();

        if (!$provider) {
            return null;
        }

        // we want GetHistoryColumns always from functions.php, so we will not transfer AccountFields to get basic Checker
        // exception: testprovider, for tests
        $checker = $this->checkerFactory->getAccountChecker($provider->getCode(), true, $provider->getState() == PROVIDER_TEST ? $account->getAccountInfo() : null);
        $hcols = $checker->GetHistoryColumns();
        $hiddenCols = $checker->GetHiddenHistoryColumns();
        array_walk($hiddenCols, function ($col) use (&$hcols) {
            unset($hcols[$col]);
        });

        // refs #13946 убираем колонку, добавляем валюту через LocalizeService::formatCurrency()
        if (isset($hcols['Currency'])) {
            unset($hcols['Currency']);
        }
        // end refs #13946

        if (
            !is_array($hcols)
            || !count($hcols)
        ) {
            return null;
        }

        $combineBonus = $checker->combineHistoryBonusToMiles();
        $bonusColumn = array_search('Bonus', $hcols);

        if ($combineBonus) {
            unset($hcols[$bonusColumn]);
        }
        $removeMilesColumn = !$combineBonus && array_search('Miles', $hcols) === false;

        $columns = $types = [];

        // Columns
        foreach (['PostingDate', 'Description', 'Miles'] as $c) {
            if (($key = array_search($c, $hcols)) !== false) {
                $columns[] = $key;
                unset($hcols[$key]);
            } else {
                $columns[] = $c;
            }
            $types[] = $c;
        }
        array_splice($columns, 2, 0, array_keys($hcols));
        array_splice($types, 2, 0, array_values($hcols));

        return [
            'columns' => $columns,
            'types' => $types,
            'removeMilesColumn' => $removeMilesColumn,
            'combineBonus' => $combineBonus,
            'bonusColumn' => $bonusColumn,
        ];
    }

    public function getAccountHistory(Account $account, $limit, $offset = 0, $includeExtra = false, $subaccount = null)
    {
        $provider = $account->getProviderid();
        $historyColumns = $this->getAccountHistoryColumns($account);

        if (!$provider || !is_array($historyColumns) || !count($historyColumns)) {
            return null;
        }

        [$columns, $types, $removeMilesColumn, $combineBonus, $bonusColumn] = array_values($historyColumns);

        $data = [];

        $isEarnPotentialProvider = in_array($provider->getProviderid(), Provider::EARNING_POTENTIAL_LIST);

        if ($isEarnPotentialProvider) {
            $this->freedomCard = $this->analyser->freedomCardConditions($account);
        }

        $andSub = !empty($subaccount) ? "AND h.SubAccountID = :subAccountID" : "AND h.SubAccountID is NULL";

        $l = (isset($offset) ? $offset . ", " : "") . $limit;
        $sql = "
            SELECT SQL_CALC_FOUND_ROWS
                h.*, 
                c.Code AS Currency, 
                m.ShoppingCategoryID as MerchantShoppingCategoryID, 
                m.Name as MerchantName, 
                sa.CreditCardID, 
                cc.Name as CardName, 
                sc.Name as ShoppingCategoryName,
                httl.TripID
            FROM
                AccountHistory h USE INDEX(HistoryDataIndex)
                LEFT JOIN Currency c ON h.CurrencyID = c.CurrencyID
                LEFT JOIN Merchant m ON h.MerchantID = m.MerchantID
                LEFT JOIN SubAccount sa ON h.SubAccountID = sa.SubAccountID 
                LEFT JOIN CreditCard cc ON sa.CreditCardID = cc.CreditCardID
                LEFT JOIN ShoppingCategory sc ON h.ShoppingCategoryID = sc.ShoppingCategoryID
                LEFT OUTER JOIN HistoryToTripLink httl on h.UUID = httl.HistoryID
            WHERE
                h.AccountID = :accountID
                $andSub
            ORDER BY
                h.PostingDate DESC, h.Position ASC
            LIMIT $l
        ";
        $sth = $this->em->getConnection()->prepare($sql);
        $sth->bindValue('accountID', $account->getAccountid());

        if ($subaccount) {
            $sth->bindParam('subAccountID', $subaccount);
        }

        $sth->execute();
        $getKey = function ($k) use ($columns) {
            return array_search($k, $columns);
        };
        $getSign = function ($value) {
            $value = floatval($value);

            if ($value > 0) {
                return "+";
            }

            return "";
        };
        $extra = [];
        $balanceCell = [];
        $stmt = $this->em->getConnection()->query('SELECT FOUND_ROWS()');
        $total = $stmt->fetch(\PDO::FETCH_NUM)[0];

        while ($fields = $sth->fetch(\PDO::FETCH_ASSOC)) {
            $d = array_fill(0, sizeof($columns), ['value' => '', 'type' => 'string']);
            $d[0]['value'] = $this->localizer->formatDateTime(new \DateTime($fields['PostingDate']), 'short', 'none');
            $d[1]['value'] = html_entity_decode($fields['Description']);

            if ($fields['Miles'] !== null && $fields['Miles'] !== '') {
                $miles = (float) $fields['Miles'];
            } else {
                $miles = null;
            }

            if ($isEarnPotentialProvider) {
                // act like no credit cards matched
                $fields['Potential'] = null;
            }

            // refs #13946 new fields logic
            foreach (['Amount', 'AmountBalance', 'MilesBalance', 'Category'] as $key) {
                if (!isset($fields[$key])) {
                    continue;
                }
                $k = array_search($key, $types);
                $value = $fields[$key];

                switch ($key) {
                    case 'Amount':
                    case 'AmountBalance':
                        $value = $this->localizer->formatCurrency((float) $value, $fields['Currency']);

                        break;

                    case 'MilesBalance':
                        $value = $this->localizer->formatNumber((float) $value);

                        break;

                    case 'Category':
                        if (!empty($fields['ShoppingCategoryName'])) {
                            $value = html_entity_decode($fields['ShoppingCategoryName']);

                            break;
                        }
                        $value = html_entity_decode($this->matchShoppingCategory($value));

                        break;
                }
                $d[$k]['value'] = $value;
            }
            unset($k, $value);
            // end refs #13946

            if (!$removeMilesColumn) {
                $row = [
                    'value' => ($miles === null ? null : $getSign($miles) . $this->localizer->formatNumber($miles)),
                    'type' => 'miles',
                ];

                if ($isEarnPotentialProvider && empty($fields['Multiplier']) && floatval($fields["Amount"]) > 0) {
                    $fields['Multiplier'] = strval(round(floatval($fields["Miles"]) / floatval($fields["Amount"]), 1));
                }

                if ($isEarnPotentialProvider && floatval($fields['Multiplier']) > 1.1) {
                    $row['multiplier'] = (strpos($fields['Multiplier'], '.0') === false ?
                        $fields['Multiplier'] : str_replace('.0', '', $fields['Multiplier'])) . 'x';
                }

                $transactionMultiplier = $row['multiplier'] ?? null;
                $d[sizeof($columns) - 1] = $row;
            } else {
                unset($d[sizeof($columns) - 1]);
            }
            $info = @unserialize($fields['Info']);

            if ($info !== false && is_array($info)) {
                foreach ($info as $name => $value) {
                    $k = $getKey($name);

                    if ($combineBonus && $name == $bonusColumn && empty($fields['Miles']) && !empty($value)) {
                        $num = (float) preg_replace('#[\.\,](\d{3})#ims', '$1', $value);
                        $d[sizeof($columns) - 1]['value'] = $getSign($num) . $this->localizer->formatNumber($num);
                    }

                    if ($k !== false && ($value || $value == "0")) {
                        //                        if (is_numeric($value)) {
                        //                            $value = $getSign($value).$this->localizer->formatNumber((float)$value);
                        //                        }
                        $d[$k]['value'] = html_entity_decode($value);
                    }
                }
            }

            if ($isEarnPotentialProvider) {
                $potential = (strpos($fields['Potential'], '.0') === false ?
                        $fields['Potential'] : str_replace('.0', '', $fields['Potential'])) . 'x';

                $everyPurchaseCategoryName = 'all purchases*';
                $offerCategory = $fields['OfferCategory'] ?? $everyPurchaseCategoryName;

                $row = [
                    'value' => '',
                    'type' => 'miles',
                    'isEp' => true,
                    'offerData' => [
                        'merchantName' => $fields['MerchantName'] ?? $fields['Description'],
                        'multiplier' => $transactionMultiplier ?? '1x',
                        'miles' => $fields['Miles'],
                        'description' => $fields['Description'],
                        'amount' => $this->localizer->formatCurrency((float) $fields['Amount'], $fields['Currency']),
                        'category' => $offerCategory,
                        'isEveryPurchaseCategory' => $offerCategory === $everyPurchaseCategoryName,
                        'cardName' => $account->getProviderid()->getDisplayname() . ' ' . $fields['CardName'],
                        'potential' => ('x' === $potential ? null : $potential),
                        'blogUrl' => $fields['BlogURL'] ?? null,
                    ],
                ];

                if (!empty($fields['MerchantName']) && floatval($fields['Multiplier']) > 0 && floatval($fields['Potential']) > floatval($fields['Multiplier'])) {
                    $epSum = round(floatval($fields['Amount']) * floatval($fields['Potential']), 2);
                    $row['value'] = $getSign($epSum) . $epSum;
                    $row['multiplier'] = $potential;
                }

                if (empty($fields['MerchantName']) && !empty($fields["Miles"]) && floatval($fields["Miles"]) > 0 && !empty($fields["Amount"])) {
                    $row['multiplier'] = '0';
                    $row['unprocess'] = true;
                }

                $d[] = $row;
            }

            $data[] = $d;

            if ($includeExtra) {
                $extra[] = [
                    'custom' => intval($fields['Custom']),
                    'note' => $fields['Note'],
                    'uuid' => $fields['UUID'],
                    'tripId' => $fields['TripID'],
                ];
            }
        }

        if (!sizeof($data)) {
            return null;
        }

        if ($isEarnPotentialProvider) {
            $columns[] = 'Earning Potential';
            $balanceCell = [sizeof($columns) - 1, sizeof($columns) - 2];
        }

        if ($removeMilesColumn && !$isEarnPotentialProvider) {
            unset($columns[sizeof($columns) - 1]);
        }

        return [
            'columns' => $columns,
            'data' => $data,
            'miles' => !$removeMilesColumn,
            'total' => intval($total),
            'extra' => $extra,
            'balance_cell' => $balanceCell,
        ];
    }

    /**
     * @param null $subAccountId
     * @return \Doctrine\DBAL\Driver\Statement
     */
    public function getBalanceHistoryStatement($accountId, $limit, $subAccountId = null)
    {
        $conn = $this->em->getConnection();
        $sth = $conn->prepare("
          SELECT
            COUNT(*) AS BalanceCount
          FROM
            AccountBalance
          WHERE
            AccountID = ? AND SubAccountID " . (isset($subAccountId) ? " = $subAccountId" : "is null"));
        $sth->execute([$accountId]);
        $row = $sth->fetch(\PDO::FETCH_ASSOC);
        $startLimit = $row['BalanceCount'] - $limit;

        if ($startLimit < 0) {
            $startLimit = 0;
        }

        $sth = $conn->prepare("
          SELECT
            UpdateDate,
            FORMAT(Balance,0) AS BalanceFormatted,
            trim(trailing '.' from trim(trailing '0' from round(Balance, 7))) as Balance
          FROM
            AccountBalance
          WHERE
            AccountID = ?
            AND SubAccountID " . (isset($subAccountId) ? " = $subAccountId" : "is null") . "
          ORDER BY
            UpdateDate
          LIMIT $startLimit, $limit
        ");
        $sth->execute([$accountId]);

        return $sth;
    }

    private function matchShoppingCategory($category)
    {
        if (empty($this->shoppingCategoryPatterns)) {
            $patterns = $this->em->getConnection()->executeQuery("SELECT * FROM ShoppingCategory WHERE Patterns IS NOT NULL");

            while ($pattern = $patterns->fetch()) {
                $this->shoppingCategoryPatterns[$pattern["Name"]] = explode("\n", $pattern["Patterns"]);
            }
        }

        if (array_key_exists($category, $this->shoppingCategoryPatterns)) {
            return $category;
        }

        // проверить в таблице ShoppingCategory на patterns
        foreach ($this->shoppingCategoryPatterns as $categoryName => $patterns) {
            foreach ($patterns as $pattern) {
                $isPreg = substr($pattern, 0, 1) === '#' ? true : false;
                $match = $isPreg ? preg_match($pattern, $category) === 1 : strpos($category, $pattern) !== false;

                if ($match) {
                    return $categoryName;
                }
            }
        }

        return $category;
    }

    private function getCountryIdByIp($ip)
    {
        $result = null;

        $country = $this->geoLocation->getCountryByIp($ip);

        if ($country instanceof Country) {
            $regionRepo = $this->em->getRepository(Region::class);
            $region = $regionRepo->findOneBy(['name' => $country->getName()]);

            if ($region instanceof Region) {
                $result = $region->getRegionid();
            }
        }

        return $result;
    }
}
