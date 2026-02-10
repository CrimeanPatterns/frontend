<?php

use AwardWallet\MainBundle\Entity\BookingInvoiceItem\CreditCardFee;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;

$schema = "payments";

require "../start.php";
drawHeader("Revenue Sources");

require_once "$sPath/manager/reports/common.php";
$bSecuredPage = false;
$sTitle = "Payments";

require __DIR__ . "/paymentsCommon.php";
echo '
    <link rel="stylesheet" type="text/css" href="/web/assets/common/vendors/select2/select2.css">
    <script src="/web/assets/common/vendors/select2/select2.js"></script>
    <style type="text/css">
    .selectTxt, .selectTxt:hover, .selectTxt:focus,
    #s2id_fldCameFrom, #s2id_fldCameFrom:hover, #s2id_fldCameFrom:focus {
        border-width: 0 !important;
    }
    </style>
    <script>
    jQuery(document).ready(function(){
        $("#fldCameFrom").select2({
            width: "450px"
        });
    });
    </script>
';

$localizer = getSymfonyContainer()->get(LocalizeService::class);
$currencyFormat = function ($str) use ($localizer) {
    return $localizer->formatCurrency($str, 'USD');
};

$fields = [
    "StartDate" => [
        "Type" => "date",
        "Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m") - 1, 1, date("Y"))),
    ],
    "EndDate" => [
        "Type" => "date",
        "Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m"), 1, date("Y"))),
    ],
    'CameFrom' => [
        'Type' => 'integer',
        'Options' => ['' => 'All'] + array_map(fn ($item) => $item['description']
                . ' [' . numFormat($item['usersCount'], 0) . ' users registered]'
                . (empty($item['cartCount']) ? '' : ' [' . numFormat($item['cartCount'], 0) . ' total carts]'), getSiteAd()
        ),
    ],
    "Button" => [
        "Type" => "html",
        "Caption" => "",
        "HTML" => getTodayButtons(),
    ],
];
$totalByPT = [];
$paymentTypes = getPaymentTypesForReports();

foreach ($paymentTypes as $key => $value) {
    $fields['PaymentType' . $key] = [
        "Type" => "boolean",
        "Caption" => $value,
        "Value" => "1",
        "Required" => false,
        "RequiredGroup" => "PaymentType",
    ];
    $totalByPT[$key] = 0;
}
$fields['PaymentTypeOneCard'] = [
    "Type" => "boolean",
    "Caption" => "OneCard processing cost",
    "Value" => "1",
    "Required" => false,
    "RequiredGroup" => "PaymentType",
];
$fields['PaymentTypeAd'] = [
    "Type" => "boolean",
    "Caption" => "Ad income",
    "Value" => "1",
    "Required" => false,
    "RequiredGroup" => "PaymentType",
];
$fields['PaymentTypeBooking'] = [
    "Type" => "boolean",
    "Caption" => "Booking income",
    "Value" => "1",
    "Required" => false,
    "RequiredGroup" => "PaymentType",
];
$fields['CreditCardAffiliateRevenue'] = [
    'Type' => 'boolean',
    'Caption' => 'Credit Card Affiliate Revenue',
    'Value' => '1',
];
$fields['Hr'] = [
    "Type" => "html",
    "Caption" => "",
    "HTML" => "<hr>",
];
$fields['CalculateCartsByCameFrom'] = [
    'Type' => 'boolean',
    'Caption' => 'Calculate carts by CameFrom',
    'Value' => 1,
];
$fields['OnlyTotals'] = [
    "Type" => "boolean",
    "Caption" => "Only totals",
    "Value" => "1",
];

$objForm = new TForm($fields);
$objForm->SubmitButtonCaption = "Calculate income";

function sqlUsersIn($objForm, $prefix = 'c.'): string
{
    if (!empty($objForm->Fields['CameFrom']['Value'])) {
        $cameFromId = (int) $objForm->Fields['CameFrom']['Value'];
        $doctrine = getSymfonyContainer()->get('doctrine');

        $usersIn = $doctrine->getConnection()->fetchAll('SELECT UserID FROM Usr WHERE CameFrom = ' . $cameFromId);

        $usersInFilter = empty($usersIn)
            ? 'UserID IN(-1)'
            : 'UserID IN (' . implode(',', array_column($usersIn, 'UserID')) . ')';
        $cameFromFilter = in_array($prefix, ['c.'], true) && 1 === (int) $objForm->Fields['CalculateCartsByCameFrom']['Value']
            ? $prefix . 'CameFrom = ' . $cameFromId
            : '';

        return ' AND (' . $prefix . $usersInFilter . (empty($cameFromFilter) ? '' : ' OR ' . $cameFromFilter) . ')';
    }

    return '';
}

if ($objForm->IsPost && $objForm->Check()) {
    echo '<style type="text/css">
tr.sumtotal td {background-color: #cfeafe !important;}
.totalStr {padding: 5px 0;border-bottom: dotted 1px #666;line-height: 20px;}
.mark-num {padding: 2px 4px;}
.totalStr:hover .mark-num {background: #fff;}
</style>';
    $isCameFrom = !empty($objForm->Fields['CameFrom']['Value']);
    $siteAdId = $isCameFrom ? (int) $objForm->Fields['CameFrom']['Value'] : null;
    $isOnlyTotal = (1 === (int) $objForm->Fields['OnlyTotals']['Value']);
    $selectedPaymentTypes = selectedPaymentTypes($objForm);

    echo $objForm->HTML();
    $objForm->CalcSQLValues();

    if (!empty($objForm->Fields['CameFrom']['Value'])) {
        $by = '<b>' . $objForm->Fields['CameFrom']['Options'][$siteAdId] . '</b>';
        $siteAd = getSiteAd($siteAdId, $selectedPaymentTypes, strtotime($objForm->Fields["StartDate"]["Value"]), strtotime($objForm->Fields["EndDate"]["Value"]));
        $byExt = false !== strpos($by, $siteAd['cartCount'] . ' total carts]')
            ? ''
            : ' &mdash; <u>Carts by condition = ' . $siteAd['cartCount'] . '</u>';
    }
    echo "<div>Displaying transactions {$objForm->Fields["StartDate"]["Value"]} <= PayDate < {$objForm->Fields["EndDate"]["Value"]} " . (empty($by) ? '' : ' by <b>' . $by . $byExt . '</b>') . "</div><br>";

    echo '
        <table class="stats" cellpadding="0" cellspacing="0" border="0">
        <tr class="head">
            <td>PayDate</td>
            <td>CartID</td>
            <td>PaymentType</td>
            <td>CouponID</td>
            <td>CouponCode</td>
            <td>Discount</td>
            <td>FirstName</td>
            <td>LastName</td>
            <td>Price</td>
            <td>Transactions fee</td>
            <td>Income</td>
            <td>Notes</td>
            <td>At201</td>
        </tr>
    ';
    $nFieldCount = 9;

    $nTotalPrice = 0;
    $nTotalFee = 0;
    $nTotalIncome = 0;
    $totalsStr = [];
    $nRecurring = 0;
    $nTotalAt201Income = 0;
    $totalAt201Transactions = 0;
    $zeroTransactions = 0;

    $nTotalAt201IncomePayable = 0;
    $at201PayableIncome = [
        116.21 => 95, // 119.99
        67.66 => 57.50, // 69.99
        14.26 => 12.91, // 14.99
    ];
    $checkHtml = [];

    // # Credit card / PayPal / App Store / Android market ##
    $rows = "";

    if (sizeof($selectedPaymentTypes)) {
        $average = 0;
        $countTransactions = 0;
        $q = new TQuery(getPaymentsSql(strtotime($objForm->Fields["StartDate"]["Value"]), strtotime($objForm->Fields["EndDate"]["Value"]), $selectedPaymentTypes, sqlUsersIn($objForm)));

        while (!$q->EOF) {
            if ($q->Fields["Price"] < 0.01) {
                $zeroTransactions++;
            }

            $rows .= "<tr>";
            calcProfit($q->Fields['PaymentType'], $q->Fields["Price"], $q->Fields["Fee"], $q->Fields["Income"]);
            $q->Fields['Notes'] = $q->Fields['Recurring'];
            unset($q->Fields['Recurring']);

            if (isset($_GET['FixCoupons']) && ($q->Fields['CouponID'] != '') && ($q->Fields['Income'] > 0)) {
                checkCouponIncome($q->Fields);
            }
            $totalByPT[$q->Fields['PaymentType']] = round($totalByPT[$q->Fields['PaymentType']] + $q->Fields["Income"], 2);
            $q->Fields['PaymentType'] = ArrayVal($paymentTypes, $q->Fields['PaymentType']);

            foreach (
                [
                    'PayDate',
                    'CartID',
                    'PaymentType',
                    'CouponID',
                    'CouponCode',
                    'Discount',
                    'FirstName',
                    'LastName',
                    'Price',
                    'Fee',
                    'Income',
                    'Notes',
                ] as $fieldKey
            ) {
                if (in_array($fieldKey, ['Price', 'Fee', 'Income'])) {
                    $rows .= '<td>' . currencyFormat($q->Fields[$fieldKey]) . '</td>';
                } else {
                    $rows .= '<td>' . htmlspecialchars($q->Fields[$fieldKey]) . '</td>';
                }
            }
            $rows .= '<td>' . (1 == $q->Fields['at201type'] ? 'at201' : '') . '</td>';

            $nTotalPrice += $q->Fields["Price"];
            $nTotalFee += $q->Fields["Fee"];
            $nTotalIncome += $q->Fields["Income"];

            if (1 == $q->Fields['at201type']) {
                $nTotalAt201Income += $q->Fields['Income'];
                $totalAt201Transactions++;

                $payIncome = $at201PayableIncome[$q->Fields['Income']] ?? null;

                if (null === $payIncome) {
                    throw new \Exception('Unknown value of the sum ' . $q->Fields['Income']);
                }
                $checkHtml[] = [$q->Fields['CartID'], $q->Fields['Price'], $q->Fields['Fee'], $q->Fields['Income'], $payIncome - $q->Fields['Fee']];
                $nTotalAt201IncomePayable += ($payIncome - $q->Fields['Fee']);
            }

            if ($q->Fields['Notes'] == 'Recurring') {
                $nRecurring += $q->Fields["Income"];
            }
            $rows .= "</tr>";
            $q->Next();
            $countTransactions++;
        }

        if ($countTransactions > 0) {
            $average = round($nTotalPrice / $countTransactions, 2);
        }

        $ar = [];

        foreach ($totalByPT as $key => $value) {
            if ($value > 0) {
                $ar[] = $paymentTypes[$key] . ": " . currencyFormat($value);
            }
        }
        $totalsStr[] = numFormat($countTransactions, 0) . " transactions, average price: " . currencyFormat($average)
            . (
                empty($ar) ? '' : "<br>
                Total income by payment method: <br/>
                " . str_repeat('&nbsp;', 5)
                . implode("<br/>" . str_repeat('&nbsp;', 5), $ar) . "<br>"
            );
        $totalsStr[] = 'AT201 group: ' . currencyFormat($nTotalAt201Income) . ", transactions: " . numFormat($totalAt201Transactions, 0);
        $totalsStr[] = 'AT201 group payable earnings: ' . currencyFormat($nTotalAt201IncomePayable) . ', <a href="#" onclick="this.classList.toggle(\'showed\')">transactions: ' . numFormat($totalAt201Transactions, 0) . '</a>' . checkHtmlResult($checkHtml);
        $totalsStr[] = "Recurring payments: " . currencyFormat($nRecurring);
        $totalsStr[] = "Zero Value Transactions: " . numFormat($zeroTransactions);
    }

    if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
        echo $rows;
    }

    // # OneCard processing cost ##
    if ($objForm->Fields["PaymentTypeOneCard"]["Value"] == "1") {
        // calculate shipped OneCard's
        calcOneCards($objForm->Fields["StartDate"]["SQLValue"], $objForm->Fields["EndDate"]["SQLValue"], $oneCardsCount, $oneCardsFee, sqlUsersIn($objForm));
        $totalsStr[] = "OneCards shipped: " . numFormat($oneCardsCount) . ", total OneCard processing cost: " . currencyFormat($oneCardsFee);
        $nTotalFee += $oneCardsFee;
        $nTotalIncome -= $oneCardsFee;
    }

    // # Ad Income, issue #6873 ##
    if ($objForm->Fields["PaymentTypeAd"]["Value"] == "1" && !$isCameFrom) {
        $adIncome = 0;

        if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
            echo "<tr class=total><td align=\"center\" colspan=" . ($nFieldCount + 4) . ">Ad income</td></tr>";
            echo "<tr class=total><td>PayDate</td>";
            echo "<td colspan=" . $nFieldCount . "></td>";
            echo "<td>Income</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }
        $q = new TQuery(
            "
                select *
                from AdIncome
                where
                PayDate >= " . $objForm->Fields["StartDate"]["SQLValue"] . "
                and PayDate <= " . $objForm->Fields["EndDate"]["SQLValue"] . "
                order by PayDate"
        );

        if ($q->EOF) {
            if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
                echo "<tr><td align=\"center\" colspan=" . ($nFieldCount + 4) . ">No data for selected period</td></tr>";
            }
        } else {
            while (!$q->EOF) {
                if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
                    echo "<tr><td>" . $q->Fields["PayDate"] . "</td>";
                    echo "<td colspan=" . $nFieldCount . "></td>";
                    echo "<td>" . currencyFormat($q->Fields["Income"]) . "</td>";
                    echo "<td colspan='2'></td>";
                    echo "</tr>";
                }
                $adIncome += $q->Fields["Income"];
                $q->Next();
            }
        }
        $nTotalIncome += $adIncome;
        $nTotalPrice += $adIncome;
        $totalsStr[] = "Ad income: " . currencyFormat($adIncome);
    }

    // # Booking income, #6780 ##
    if ($objForm->Fields["PaymentTypeBooking"]["Value"] == "1") {
        $bookingIncome = 0;

        if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
            echo "<tr class=total><td align=\"center\" colspan=" . ($nFieldCount + 4) . ">Booking income</td></tr>";
            echo "<tr class=total><td>Process date</td>";
            echo "<td colspan=" . $nFieldCount . "></td>";
            echo "<td>Income</td>";
            echo "<td colspan='2'></td>";
            echo "</tr>";
        }
        $q = new TQuery("
            SELECT DISTINCT 
              tr.AbTransactionID,
              tr.ProcessDate,
              ROUND(t.Total * bi.InboundPercent / 100, 2) AS AWearnings
            FROM 
              AbTransaction tr
              JOIN AbInvoice i ON i.TransactionID = tr.AbTransactionID
              JOIN AbMessage m ON m.AbMessageID = i.MessageID 
              JOIN AbRequest r ON r.AbRequestID = m.RequestID 
              JOIN (
                SELECT 
                  t.AbTransactionID, 
                  SUM(
                    IF(
                      it.Type <> " . CreditCardFee::TYPE . ", 
                      ROUND(
                        it.Price * it.Quantity - (
                          it.Price * it.Quantity * COALESCE(it.Discount, 0) / 100
                        ), 
                        2
                      ), 
                      0
                    )
                  ) AS Total 
                FROM 
                  AbInvoiceItem it
                  JOIN AbInvoice i ON i.AbInvoiceID = it.AbInvoiceID
                  JOIN AbTransaction t ON t.AbTransactionID = i.TransactionID 
                GROUP BY 
                  t.AbTransactionID
              ) t ON t.AbTransactionID = tr.AbTransactionID
              JOIN AbBookerInfo bi ON bi.UserID = r.BookerUserID
            WHERE
              tr.ProcessDate >= " . $objForm->Fields["StartDate"]["SQLValue"] . "
              AND tr.ProcessDate <= " . $objForm->Fields["EndDate"]["SQLValue"] . "
              AND tr.Processed = 1
              " . sqlUsersIn($objForm, 'm.') . "
            ORDER BY tr.ProcessDate
        ");

        if ($q->EOF) {
            if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
                echo "<tr><td align=\"center\" colspan=" . ($nFieldCount + 4) . ">No data for selected period</td></tr>";
            }
        } else {
            foreach ($q as $row) {
                $bookingIncome += $row['AWearnings'];

                if ($objForm->Fields['OnlyTotals']['Value'] != "1") {
                    echo "<tr><td>" . $row['ProcessDate'] . "</td>";
                    echo "<td colspan=" . $nFieldCount . "></td>";
                    echo "<td>" . currencyFormat($row['AWearnings']) . "</td>";
                    echo "<td></td>";
                    echo "</tr>";
                }
            }
        }
        $nTotalIncome += $bookingIncome;
        $nTotalPrice += $bookingIncome;
        $totalsStr[] = "Booking income: " . currencyFormat($bookingIncome) . "<br>";
    }

    if (1 === (int) $objForm->Fields['CreditCardAffiliateRevenue']['Value']) {
        $connection = getSymfonyContainer()->get('doctrine')->getConnection();
        $rows = $connection->fetchAll('
            SELECT
               ProcessDate, SUM(Earnings) as _sumEarnings
            FROM QsTransaction
            WHERE
                    ProcessDate BETWEEN ' . $objForm->Fields['StartDate']['SQLValue'] . ' AND ' . $objForm->Fields['EndDate']['SQLValue'] . '
                AND Approvals = 1
                ' . sqlUsersIn($objForm, '') . ' 
            GROUP BY ProcessDate
        ');

        $html = '';
        $cardAffiliateRevenueIncome = 0;

        if (!$isOnlyTotal) {
            $html .= '
                <tr class="total"><td align="center" colspan="' . ($nFieldCount + 4) . '">' . $fields['CreditCardAffiliateRevenue']['Caption'] . '</td></tr>
                <tr class="total">
                    <td>Process date</td>
                    <td colspan="' . $nFieldCount . '"></td>
                    <td>Income</td>
                    <td colspan="2"></td>
                </tr>
            ';
        }

        if ($isOnlyTotal) {
            $cardAffiliateRevenueIncome = array_sum(array_column($rows, '_sumEarnings'));
        } elseif (empty($rows)) {
            $html .= '<tr><td align="center" colspan="' . ($nFieldCount + 4) . '">No data for selected period</td></tr>';
        } else {
            foreach ($rows as $row) {
                $cardAffiliateRevenueIncome += $row['_sumEarnings'];
                $html .= '<tr>'
                    . '<td>' . $row['ProcessDate'] . '</td>'
                    . '<td colspan="' . $nFieldCount . '"></td>'
                    . '<td>' . currencyFormat($row['_sumEarnings']) . '</td>'
                    . '<td colspan="2"></td>'
                    . '</tr>';
            }
        }
        echo $html;

        $nTotalIncome += $cardAffiliateRevenueIncome;
        $nTotalPrice += $cardAffiliateRevenueIncome;
        $totalsStr[] = $fields['CreditCardAffiliateRevenue']['Caption'] . ': ' . currencyFormat($cardAffiliateRevenueIncome) . '<br>';
    }

    echo "<tr class='total sumtotal'>";
    echo "<td colspan=" . ($nFieldCount - 1) . "><div class='totalStr'>" . implode('</div><div class="totalStr">', $totalsStr) . "</div></td>";
    echo "<td>" . currencyFormat($nTotalPrice) . "</td>";

    if ($nTotalFee > 0) {
        echo "<td>" . currencyFormat($nTotalFee) . "</td>";
    } else {
        echo "<td></td>";
    }
    echo "<td>" . currencyFormat($nTotalIncome) . "</td>";
    echo "<td colspan='2'></td>";
    echo "</tr>";
    echo "</table>";
} else {
    echo $objForm->HTML();
}

if (!empty($objForm->Fields['CameFrom']['Value'])) {
    $usersCount = (int) getSiteAd((int) $objForm->Fields['CameFrom']['Value'])['usersCount'];

    if (!empty($usersCount)) {
        echo '<div style="padding: 1rem 0;font-size: 20px;">User cost: '
            . currencyFormat($nTotalIncome) . ' / ' . numFormat($usersCount, 0) . ' = '
            . '<b>' . currencyFormat($nTotalIncome / $usersCount) . '</b>'
            . ' </div>';
    }
}

// see calcProfit()
echo '<br><br><hr><br>
Fees by PaymentType:<br>
 &mdash; AppleStore: ' . \AwardWallet\MainBundle\Entity\Cart::APPSTORE_FEES_PERCENT . '%<br>
 &mdash; AndroidMarket: ' . \AwardWallet\MainBundle\Entity\Cart::ANDROIDMARKET_FEES_PERCENT . '%<br>
 &mdash; PayPal: 0<br>
 &mdash; Credit Card (PayPal): %REVENUE% * 0.0349 + 0.49<br>
 &mdash; Stripe: %REVENUE% * 0.029 + 0.30
 <br><br>
';

function checkCouponIncome(&$fields)
{
    global $Connection;
    $sql = "select CartItemID from CartItem where CartID = {$fields['CartID']}";
    $q = new TQuery($sql);

    while (!$q->EOF) {
        $Connection->Execute("update CartItem set Discount = {$fields['Discount']}
		where CartItemID = {$q->Fields["CartItemID"]}");
        $q->Next();
    }
    $fields['Notes'] = 'Discount fixed';
}

function selectedPaymentTypes($form)
{
    global $paymentTypes;
    $result = [];

    foreach ($paymentTypes as $key => $value) {
        if ($form->Fields["PaymentType" . $key]["Value"] == "1") {
            $result[] = $key;
        }
    }

    return $result;
}

function getSiteAd($siteAdId = null, ?array $selectedPaymentTypes = null, $startTime = 0, $endTime = 0)
{
    static $result = [];

    if (!empty($result) && null === $selectedPaymentTypes) {
        if (!empty($siteAdId)) {
            return $result[$siteAdId];
        }

        return $result;
    }

    $rows = getSymfonyContainer()->get('doctrine')->getConnection()->fetchAll("
        SELECT
            s.SiteAdID, s.Description,
            COUNT(u.UserID) AS _usersCount
        FROM SiteAd s
        LEFT JOIN Usr u ON (u.CameFrom = s.SiteAdID)
        GROUP BY s.SiteAdID, s.Description
        ORDER BY s.Description ASC
    ");
    $dateBetween = !empty($startTime) && !empty($endTime)
        ? "BETWEEN '" . date('Y-m-d H:i:s', $startTime) . "' AND '" . date('Y-m-d H:i:s', $endTime) . "'"
        : '';
    $cartsRef = getSymfonyContainer()->get('doctrine')->getConnection()->fetchAll("
        SELECT
            s.SiteAdID, s.Description,
            COUNT(c.CartID) AS _cartCount
        FROM SiteAd s
        JOIN Cart c ON (c.CameFrom = s.SiteAdID " . (
        empty($selectedPaymentTypes)
            ? '' :
            'AND c.PaymentType IN(' . implode(',', array_map('intval', $selectedPaymentTypes)) . ')'
    ) . ")
        WHERE
                c.PaymentType IS NOT NULL
            " . (empty($dateBetween) ? '' : 'AND c.PayDate ' . $dateBetween) . "
            AND c.CartID NOT IN (
                SELECT c2.CartID 
                FROM Cart c2
                JOIN CartItem ci2 ON (c2.CartID = ci2.CartID)
                WHERE
                    ci2.ScheduledDate IS NOT NULL
                    " . (empty($dateBetween) ? '' : 'AND c2.PayDate ' . $dateBetween) . "
            )
        GROUP BY s.SiteAdID, s.Description
        ORDER BY s.Description ASC
    ");
    $cartsRef = array_combine(array_column($cartsRef, 'SiteAdID'), $cartsRef);
    $list = [];

    foreach ($rows as $row) {
        $list[$row['SiteAdID']] = [
            'description' => $row['Description'],
            'usersCount' => $row['_usersCount'],
            'cartCount' => array_key_exists($row['SiteAdID'], $cartsRef) ? $cartsRef[$row['SiteAdID']]['_cartCount'] : 0,
        ];
    }

    if (!empty($siteAdId)) {
        return $list[$siteAdId];
    }

    return $result = $list;
}

function numFormat($number, $decimals = 2)
{
    return number_format($number, $decimals);
}
function currencyFormat($number)
{
    global $currencyFormat;

    return '<span class="mark-num">' . $currencyFormat($number) . '</span>';
}

drawFooter();

function checkHtmlResult($data): string
{
    $result = '
<style>
.checkTable {margin: 1rem;display: none;}
.checkTable, .checkTable * {padding: 0 5px;border-collapse: collapse;border:1px solid #333 !important;}
.checkTable thead {background-color: #fff;}
.checkTable tfoot {background-color: #ddd;}
a.showed ~ .checkTable {display: table !important;}
</style>
<table class="checkTable">';
    $result .= '<thead><tr><th>CartID</th><th>Price</th><th>Fee</th><th>Income</th><th>Payable</th></tr></thead>';

    $price = 0;
    $fee = 0;
    $income = 0;
    $payable = 0;

    foreach ($data as $key => $value) {
        $result .= '<tr><td>';
        $result .= implode('</td><td>', $value);
        $result .= '</td></tr>';

        $price += $value[1];
        $fee += $value[2];
        $income += $value[3];
        $payable += $value[4];
    }

    $result .= '<tfoot><tr><th>total:</th><th>' . currencyFormat($price) . '</th><th>' . currencyFormat($fee) . '</th><th>' . currencyFormat($income) . '</th><th>' . currencyFormat($payable) . '</th></tr></tfoot>';

    $result .= '</table>';

    return $result;
}
