<?php

namespace AwardWallet\MainBundle\Controller\Manager\User;

use AwardWallet\MainBundle\Entity\CreditCard;
use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Service\CreditCards\Commands\Helpers\SnapshotTable;
use AwardWallet\MainBundle\Service\CreditCards\Schema\ProviderOptions;
use AwardWallet\MainBundle\Service\OldUI;
use AwardWallet\MainBundle\Worker\AsyncProcess\AsyncControllerAction;
use Clock\ClockNative;
use Doctrine\DBAL\Connection;
use Duration\Duration;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TransactionListController
{
    /**
     * @Security("is_granted('ROLE_MANAGE_MERCHANT')")
     * @Route("/manager/transaction-list", name="aw_manager_transaction_list")
     */
    public function reportAction(
        Request $request,
        OldUI $oldUI,
        AsyncControllerAction $asyncControllerAction,
        ProviderOptions $providerOptions,
        ParameterRepository $paramRepository,
        Connection $dbConnection
    ) {
        $recentOnlyParamUnsafe = $request->query->get('RecentOnly');
        $suffixData = null;

        if (StringUtils::isNotEmpty($recentOnlyParamUnsafe)) {
            $suffixData = self::matchTableSuffix($recentOnlyParamUnsafe);

            if (!$suffixData) {
                $suffixData = self::matchTableSuffix($paramRepository->getParam(ParameterRepository::LAST_TRANSACTIONS_DATE));
            }
        }

        $response = $asyncControllerAction->renderProgress(
            $request,
            $suffixData ?
                "Transaction list (Posting Date from " . $suffixData[0]->modify("-{$suffixData[1]} days")->format('Y-m-d H:i:s') . " to " . $suffixData[0]->format('Y-m-d H:i:s') . " ({$suffixData[1]} days) UTC)" :
                "Transaction list"
        );

        if ($response !== null) {
            return $response;
        }

        $groups = SQLToArray("select ShoppingCategoryGroupID, Name 
            from ShoppingCategoryGroup order by Name", "ShoppingCategoryGroupID", "Name");

        $CAHSHBACK_TYPE_POINT = CreditCard::CASHBACK_TYPE_POINT;

        $fields = [
            "UserID" => [
                "Type" => "integer",
                "FilterField" => "a.UserID",
            ],
            "AccountID" => [
                "Type" => "integer",
                'FilterField' => 'h.AccountID',
            ],
            "SubAccountID" => [
                "Type" => "integer",
                'FilterField' => 'h.SubAccountID',
            ],
            "PostingDate" => [
                "Type" => "date",
                "Sort" => "PostingDate DESC",
            ],
            "Description" => [
                "Type" => "string",
                "FilterField" => "h.Description",
            ],
            "MerchantID" => [
                "Type" => "integer",
                "FilterField" => "h.MerchantID",
            ],
            "Category" => [
                "Type" => "string",
                "Caption" => "Category (parsed)",
            ],
            "ShoppingCategoryID" => [
                "Type" => "integer",
                "Caption" => "History Shopping Category",
                "FilterField" => "h.ShoppingCategoryID",
                "Options" => [0 => 'No Category'] + SQLToArray("select ShoppingCategoryID, Name 
        			    from ShoppingCategory order by Name", "ShoppingCategoryID", "Name"),
            ],
            "HistoryShoppingCategoryGroupID" => [
                "Type" => "integer",
                "Caption" => "History Shopping Category Group",
                "FilterField" => "hsc.ShoppingCategoryGroupID",
                "Options" => $groups,
            ],
            "ShoppingCategoryGroupID" => [
                "Type" => "integer",
                "Caption" => "Merchant Shopping Category Group",
                "FilterField" => "sc.ShoppingCategoryGroupID",
                "Options" => $groups,
            ],
            "CreditCardID" => [
                "Type" => "integer",
                "Caption" => "Credit Card",
                "FilterField" => "s.CreditCardID",
                "Options" => SQLToArray("
                        select cc.CreditCardID, CONCAT(p.Name, ': ', cc.Name) as Name
        			    from CreditCard cc
        			    join Provider p on  p.ProviderID = cc.ProviderID
        			    order by Name", "CreditCardID", "Name"),
            ],
            "ProviderID" => [
                "Type" => "integer",
                "Caption" => "Provider",
                "FilterField" => "a.ProviderID",
                "Options" => $providerOptions->getOptions(),
            ],
            "Amount" => [
                "Type" => "float",
            ],
            "Miles" => [
                "Type" => "float",
            ],
            "Multiplier" => [
                "Type" => "float",
                "FilterField" => /** @lang SQL */ "cast(coalesce(
                    if(
                        cc.IsCashBackOnly AND cc.CashBackType <> {$CAHSHBACK_TYPE_POINT}, 
                        round(round(h.Miles * 100) / h.Amount, 1),
                        null
                    ),
                    h.Multiplier,
                    round(h.Miles / h.Amount, 1)
                ) as decimal(10, 1))",
            ],
            "ExpectedMultiplier" => [
                "Type" => "float",
                "FilterField" => /** @lang SQL */ "COALESCE(
                    greatest(ccmg.Multiplier, ccscg.Multiplier),
                    ccmg.Multiplier,
                    ccscg.Multiplier
                )",
            ],
            "IsExpectedMultiplier" => [
                "Type" => "boolean",
                "FilterField" => /** @lang SQL */
                "IF(ABS(
                    cast(coalesce(
                        if(
                            cc.IsCashBackOnly AND cc.CashBackType <> {$CAHSHBACK_TYPE_POINT}, 
                            round(round(h.Miles * 100) / h.Amount, 1),
                            null
                        ),
                        h.Multiplier,
                        round(h.Miles / h.Amount, 1)
                    ) as decimal(10, 1)) -
                    COALESCE(
                        greatest(ccmg.Multiplier, ccscg.Multiplier),
                        ccmg.Multiplier,
                        ccscg.Multiplier
                    ) 
                ) < 0.5, 1, 0)",
            ],
            "UUID" => [
                "Type" => "string",
            ],
        ];

        $list = new class($dbConnection, $paramRepository, $suffixData, $fields, 'UUID') extends \TBaseList {
            private Duration $queryTime;
            private ParameterRepository $parameterRepository;
            private bool $hasTable = true;
            private ?array $suffixData;

            public function __construct(Connection $dbConnection, ParameterRepository $parameterRepository, ?array $suffixData, $fields, $defaultSort = null, ?\Symfony\Component\HttpFoundation\Request $request = null)
            {
                $this->parameterRepository = $parameterRepository;
                $table = 'AccountHistory';

                if ($suffixData) {
                    [$rightDate, $days] = $suffixData;
                    $tableCandidate = 'LastTransactionsExamples' . SnapshotTable::makeSuffix($rightDate, $days);
                    $this->hasTable = (bool) $dbConnection->executeQuery('show tables like ?', [$tableCandidate])->fetchOne();

                    if ($this->hasTable) {
                        $table = $tableCandidate;
                    }
                }

                parent::__construct($table, $fields, $defaultSort, $request);
                $this->suffixData = $suffixData;
            }

            public function DrawHeader()
            {
                if ($this->hasTable) {
                    echo "Took " . \number_format($this->queryTime->getAsMinutesFractionFloat(), 1) . ' min(s)';
                } else {
                    $currentParams = $this->request->query->all();
                    $newerVersion = $this->parameterRepository->getParam(ParameterRepository::LAST_TRANSACTIONS_DATE);
                    $actualQuery = \http_build_query(\array_merge(
                        $currentParams,
                        ['RecentOnly' => $newerVersion]
                    ));
                    unset($currentParams['RecentOnly']);
                    $fullQuery = \http_build_query($currentParams);
                    echo "<h2>"
                      . "Your link to recent transactions is obsolete, you can proceed either with"
                      . " <a href='?{$actualQuery}' title='{$newerVersion}'>more recent data</a>"
                      . " or <a href='?{$fullQuery}' title='FULL'>full data</a>"
                      . "</h2>";
                }

                parent::DrawHeader();
            }

            public function OpenQuery()
            {
                if ($this->hasTable) {
                    $this->queryTime = (new ClockNative())->stopwatch(fn () => parent::OpenQuery());
                } else {
                    $this->Query = new \TQuery();
                    $this->Query->EOF = true;
                    $this->Fields = [];
                }
            }

            public function GetFilterFields()
            {
                $result = parent::GetFilterFields();

                $result['StartDate'] = [
                    'Type' => 'date',
                    'InputAttributes' => 'form="list_filter_form"',
                ];
                $result['EndDate'] = [
                    'Type' => 'date',
                    'InputAttributes' => 'form="list_filter_form"',
                ];

                return $result;
            }

            public function DrawFiltersForm(&$arHiddens)
            {
                if (!$this->hasTable) {
                    return;
                }

                parent::DrawFiltersForm($arHiddens);

                echo "<tr><td colspan=" . (count($this->Fields) + 1) . "><table><tr>\n";

                foreach (['Start', 'End'] as $prefix) {
                    echo "<td>{$prefix} Date</td>";
                    $this->DrawFieldFilter($prefix . "Date", $this->FilterForm->Fields[$prefix . 'Date']);
                    unset($arHiddens[$prefix . 'Date']);
                }

                if ($this->suffixData) {
                    $suffixTable = SnapshotTable::makeSuffix($this->suffixData[0], $this->suffixData[1]);
                    echo "<input type='hidden' form='list_filter_form' name='RecentOnly' value='{$suffixTable}'/>";
                }

                echo "</tr></table></td></tr>\n";
            }

            public function GetFieldFilter($sField, $arField)
            {
                if ($sField === 'StartDate') {
                    return " and h.PostingDate >= {$arField["SQLValue"]}";
                }

                if ($sField === 'EndDate') {
                    return " and h.PostingDate < date_add( {$arField["SQLValue"]}, interval 1 day )";
                }

                return parent::GetFieldFilter($sField, $arField);
            }

            public function FormatFields($output = "html")
            {
                $accUpdateDate = $this->Query->Fields['AccountUpdateDate'];
                $subAccountDisplayName = $this->Query->Fields['SubAccountDisplayName'];
                unset($this->Query->Fields['AccountUpdateDate']);
                unset($this->Query->Fields['SubAccountDisplayName']);
                $expectedMultiplier = \json_decode($this->Query->Fields['ExpectedMultiplier'], true);
                parent::FormatFields($output);
                $router = getSymfonyContainer()->get('router');
                $detailsLink = $router->generate('multiplier_lookup_show', ['id' => $this->Query->Fields['UUID']]);

                if ($this->Query->Fields['CCID'] !== null) {
                    $ccLink = $router->generate('credit_card_edit', ['id' => $this->Query->Fields['CCID']]);
                    $this->Query->Fields['CreditCardID'] = "<a target='_blank' href='{$ccLink}' title='" . \htmlspecialchars($subAccountDisplayName) . "'>" . $this->Query->Fields['CreditCardID'] . "</a>";
                    $this->Query->Fields['SubAccountID'] = "<span title='{$subAccountDisplayName}'>" . \htmlspecialchars($this->Query->Fields['SubAccountID']) . "</span>";
                }
                $this->Query->Fields['UUID'] = "<a target='_blank' href='{$detailsLink}'>" . $this->Query->Fields['UUID'] . "</a>";
                $this->Query->Fields['ExpectedMultiplier'] = (isset($expectedMultiplier['scg']) || isset($expectedMultiplier['mpg'])) ?
                    (
                        $this->formatMultiplierData(
                            $expectedMultiplier['scg'],
                            'SCG',
                            'Shopping Category Group',
                            '/manager/list.php?ShoppingCategoryGroupID=%d&Schema=ShoppingCategoryGroup'
                        )
                        . '<br/>'
                        . $this->formatMultiplierData(
                            $expectedMultiplier['mpg'],
                            'MPG',
                            'Merchant Pattern Group',
                            '/manager/list.php?MerchantGroupID=%d&Schema=MerchantGroup',
                        )
                    ) :
                    null;
                $this->Query->Fields['AccountID'] = "<span title='Updated: {$accUpdateDate}'>{$this->Query->Fields['AccountID']}</span>";
                $merchantId = $this->Query->Fields['MerchantID'];
                $this->Query->Fields['MerchantID'] = isset($merchantId) ?
                    "<a href='/manager/list.php?Schema=Merchant&MerchantID={$merchantId}' target='_blank'>{$merchantId}</a>" :
                    null;
            }

            protected function formatMultiplierData(?array $data, string $short, string $schemaTitle, string $linkTamplate): string
            {
                return null !== $data ?
                    (
                        "<span title='{$schemaTitle}: {$data['groupName']}'>"
                        . "<a target='_blank' href='" . \sprintf($linkTamplate, $data['groupId']) . "'>{$short}</a>"
                        . ': ' . \number_format($data['multiplier'], 1)
                    ) :
                    "<span title='Shopping Category Group: none'>{$short}: none</span>"
                ;
            }

            public function GetOrderBy()
            {
                if (!$this->request->query->has('Sort1')) {
                    return '';
                }

                return parent::GetOrderBy();
            }

            public function GetFilters($filterType = "where")
            {
                $isRewrited = false;

                if ($this->request->query->get('ShoppingCategoryID') == "0" && $filterType === "where") {
                    $this->request->query->remove('ShoppingCategoryID');
                    $isRewrited = true;
                }
                $filters = parent::GetFilters($filterType);

                if ($isRewrited && $filterType === "where") {
                    $this->request->query->set('ShoppingCategoryID', '0');
                    $this->FilterForm->Fields['ShoppingCategoryID']['Value'] = '0';
                    $filters = ' h.ShoppingCategoryID is null and' . $filters;
                }

                return $filters;
            }
        };

        $list->PageSize = 100;
        $list->Limit = 500;
        $list->SQL = "
            SELECT
                h.UUID,
                h.AccountID,
                h.SubAccountID,
                h.PostingDate,
                h.Description,
                h.MerchantID,
                h.Category,
                h.ShoppingCategoryID,
                sc.ShoppingCategoryGroupID,
                hsc.ShoppingCategoryGroupID as HistoryShoppingCategoryGroupID,
                h.Amount,
                h.Miles,
                cast(coalesce(
                    if(
                        cc.IsCashBackOnly AND cc.CashBackType <> {$CAHSHBACK_TYPE_POINT},
                        round(round(h.Miles * 100) / h.Amount, 1),
                        null
                    ),
                    h.Multiplier,
                    round(h.Miles / h.Amount, 1)
                ) as decimal(10, 1)) as Multiplier,
                a.UserID,
                a.ProviderID,
                a.UpdateDate as AccountUpdateDate,
                s.CreditCardID,
                s.CreditCardID as CCID,
                s.DisplayName as SubAccountDisplayName,
                JSON_OBJECT(
                    'mpg', if(ccmg.Multiplier is not null,
                        json_object(
                            'multiplier', ccmg.Multiplier,
                            'groupName', mg.Name,
                            'groupId', mg.MerchantGroupID
                        ),
                        null
                    ),
                    'scg', if(ccscg.Multiplier is not null,
                        json_object(
                            'multiplier', ccscg.Multiplier,
                            'groupName', scg.Name,
                            'groupId', scg.ShoppingCategoryGroupID
                        ),
                        null
                    )
                ) as ExpectedMultiplier,
                IF(ABS(
                    cast(coalesce(
                        if(
                            cc.IsCashBackOnly AND cc.CashBackType <> {$CAHSHBACK_TYPE_POINT},
                            round(round(h.Miles * 100) / h.Amount, 1),
                            null
                        ),
                        h.Multiplier,
                        round(h.Miles / h.Amount, 1)
                    ) as decimal(10, 1)) -
                    COALESCE(
                        greatest(ccmg.Multiplier, ccscg.Multiplier),
                        ccmg.Multiplier,
                        ccscg.Multiplier
                    )
                ) < 0.5, 1, 0) as IsExpectedMultiplier
            FROM {$list->Table} h
                JOIN Account a ON h.AccountID = a.AccountID
                JOIN SubAccount s ON s.SubAccountID = h.SubAccountID
                JOIN Merchant m ON h.MerchantID = m.MerchantID
                LEFT JOIN CreditCard cc on s.CreditCardID = cc.CreditCardID
                LEFT JOIN ShoppingCategory hsc on h.ShoppingCategoryID = hsc.ShoppingCategoryID
                LEFT JOIN ShoppingCategory sc on m.ShoppingCategoryID = sc.ShoppingCategoryID
                LEFT JOIN ShoppingCategoryGroup scg ON sc.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                LEFT JOIN CreditCardShoppingCategoryGroup ccscg ON
                    ccscg.ShoppingCategoryGroupID = scg.ShoppingCategoryGroupID
                    AND ccscg.CreditCardID = s.CreditCardID
                    AND (
                        ccscg.StartDate is null
                        or ccscg.StartDate <= DATE(h.PostingDate)
                    )
                    and (
                        ccscg.EndDate is null
                        or ccscg.EndDate > DATE(h.PostingDate)
                    )
                left join MerchantPatternGroup mpg on m.MerchantPatternID = mpg.MerchantPatternID
                left join MerchantGroup mg on mpg.MerchantGroupID = mg.MerchantGroupID
                left join CreditCardMerchantGroup ccmg on
                    ccmg.MerchantGroupID = mpg.MerchantGroupID
                    and ccmg.CreditCardID = s.CreditCardID
                    and (
                        ccmg.StartDate is null
                        or ccmg.StartDate <= DATE(h.PostingDate)
                    )
                    and (
                        ccmg.EndDate is null
                        or ccmg.EndDate > DATE(h.PostingDate)
                    )";
        ob_start();

        $list->ReadOnly = true;
        $list->ShowFilters = true;
        $list->PageSize = 100;

        $list->Draw();
        echo <<<'JS'
        <script>
            function matchAllTerms(term, text, opt) {
                // If no term, show all options
                if (!term) return true;
                
                // Convert both to uppercase for case-insensitive matching
                const upperText = text.toUpperCase();
                const upperTerm = term.toUpperCase();
                
                // Split term by space and trim each part
                const termParts = upperTerm.split(/\s+/).filter(part => part.length > 0);
                
                // Check if all parts of the term are found in the text
                return termParts.every(part => upperText.indexOf(part) >= 0);
            }
            $('select[name="CreditCardID"]').select2({width: '200px', dropdownAutoWidth: true, matcher: matchAllTerms});
            $('select[name="ShoppingCategoryID"]').select2({width: '200px', dropdownAutoWidth: true, matcher: matchAllTerms});
        </script>
JS;

        return new Response(ob_get_clean());
    }

    /**
     * @return array{0: \DateTimeImmutable, 1: ?int}|null
     */
    public static function matchTableSuffix(string $tableSuffix): ?array
    {
        if (\preg_match('#^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})_(\d+)d$#', $tableSuffix, $matches)) {
            [$_, $year, $month, $day, $hour, $minute, $seconds] = $matches;

            return [
                new \DateTimeImmutable("{$year}-{$month}-{$day} {$hour}:{$minute}:{$seconds}"),
                (int) $matches[7],
            ];
        }

        return null;
    }
}
