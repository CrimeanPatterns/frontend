<?php

function getProfit(?int $startDate = null, ?int $endDate = null, ?string $where = null)
{
    $q = new TQuery(getPaymentsSql($startDate, $endDate, null, $where));
    $result = [];
    $result["nTotalRevenue"] = $result["nTotalFee"] = $result["nTotalProfit"] = $result["nTransactions"] = 0;

    while (!$q->EOF) {
        calcProfit($q->Fields["PaymentType"], $q->Fields["Price"], $q->Fields["Fee"], $q->Fields["Income"]);
        $result["nTotalRevenue"] += $q->Fields["Price"];
        $result["nTotalFee"] += $q->Fields["Fee"];
        $result["nTotalProfit"] += $q->Fields["Income"];
        $result["nTransactions"]++;
        $q->Next();
    }

    return $result;
}

function calcProfit($paymentType, &$revenue, &$fee, &$profit)
{
    $revenue = round($revenue, 2);

    if ($revenue > 0) {
        switch ($paymentType) {
            case PAYMENTTYPE_APPSTORE:
                // $fee = round(($revenue - 0.01) * 0.30, 2);
                $percent = \AwardWallet\MainBundle\Entity\Cart::APPSTORE_FEES_PERCENT / 100;
                $fee = round($revenue * $percent, 2);

                break;

            case PAYMENTTYPE_ANDROIDMARKET:
                // $fee = round($revenue * 0.30, 2);
                $percent = \AwardWallet\MainBundle\Entity\Cart::ANDROIDMARKET_FEES_PERCENT / 100;
                $fee = round($revenue * $percent, 2);

                break;

            case PAYMENTTYPE_PAYPAL:
                $fee = 0;

                break;

            case PAYMENTTYPE_STRIPE_INTENT:
                $fee = round($revenue * 0.029 + 0.30, 2);

                break;

            default:
                $fee = round($revenue * 0.0349 + 0.49, 2);
        }
    } else {
        $fee = 0;
    }
    $profit = $revenue - $fee;
}

function getTotals()
{
    global $Connection, $USERS_TO_EXCLUDE;
    $result = [];
    $q = new TQuery("SELECT COUNT(*) AS totalUsers FROM Usr Where CreationDateTime > '2004-11-20';", $Connection);
    $result['TotalUsers'] = $q->Fields['totalUsers'];
    $q = new TQuery("SELECT COUNT(*) AS totalUsers FROM Usr Where CreationDateTime > '2004-11-20' AND UserID IN (SELECT DISTINCT UserID FROM Account);", $Connection);
    $result['NonEmptyUsers'] = $q->Fields['totalUsers'];
    $q = new TQuery("SELECT COUNT(a.AccountID) AS totalAccounts FROM Account a INNER JOIN Usr u ON u.UserID = a.UserID Where u.CreationDateTime > '2004-11-20'", $Connection);
    $result['TotalAccounts'] = $q->Fields['totalAccounts'];
    $q = new TQuery("SELECT max(UserID) as lastUser FROM Usr", $Connection);
    $result['LastUser'] = $q->Fields['lastUser'];
    $result['AveragePrograms'] = floor($result['TotalAccounts'] / $result['NonEmptyUsers'] * 100) / 100;
    $q = new TQuery("SELECT COUNT(DISTINCT UserID) AS usersSinceToday FROM Account WHERE UpdateDate > '" . date("Y") . "-" . date("m") . "-" . date("d") . " 00:00:00' AND UserID NOT IN (" . implode(",", $USERS_TO_EXCLUDE) . ")");
    $result['UsersSinceToday'] = $q->Fields['usersSinceToday'];
    $q = new TQuery("SELECT COUNT(AccountID) AS checkedSinceToday FROM Account WHERE UpdateDate > '" . date("Y") . "-" . date("m") . "-" . date("d") . " 00:00:00' AND UserID NOT IN (" . implode(",", $USERS_TO_EXCLUDE) . ")");
    $result['CheckedSinceToday'] = $q->Fields['checkedSinceToday'];
    $q = new TQuery("SELECT COUNT(UserID) AS ReturningUsers FROM Usr WHERE CreationDateTime <> LastLogonDateTime AND UserID NOT IN (" . implode(",", $USERS_TO_EXCLUDE) . ")");
    $result['ReturningUsers'] = $q->Fields['ReturningUsers'];
    $q = new TQuery("SELECT COUNT(*) AS AllTrips FROM Trip");
    $result['AllTrips'] = $q->Fields["AllTrips"];
    $q = new TQuery("SELECT COUNT(distinct c.UserID) AS PayingUsersNow FROM Cart c
	JOIN Usr u on c.UserID = u.UserID
	WHERE c.BillingTransactionID IS NOT NULL
	AND u.AccountLevel = " . ACCOUNT_LEVEL_AWPLUS);
    $result['PayingUsersNow'] = $q->Fields["PayingUsersNow"];
    $totalIncomeAr = getProfit();
    $result['TotalProfit'] = '$' . number_format($totalIncomeAr["nTotalProfit"], 2, ".", ",");
    $monthIncomeAr = getProfit(strtotime(date("Y-m-01")));
    $result['MonthProfit'] = '$' . number_format($monthIncomeAr["nTotalProfit"], 2, ".", ",");
    $q = new TQuery("SELECT COUNT(distinct t.TripID) AS Total FROM Trip t, TripSegment ts WHERE t.TripID = ts.TripID and ts.DepDate > now() and t.Category = " . TRIP_CATEGORY_AIR);
    $result['Trips'] = $q->Fields["Total"];
    $q = new TQuery("SELECT COUNT(*) AS Total FROM Reservation WHERE CheckInDate > now()");
    $result['Reservations'] = $q->Fields["Total"];
    $q = new TQuery("SELECT COUNT(*) AS Total FROM Rental WHERE PickupDatetime > now()");
    $result['Rentals'] = $q->Fields["Total"];
    $q = new TQuery("SELECT COUNT(*) AS Total FROM Usr WHERE UserID IN (SELECT DISTINCT UserID FROM Account) AND LogonCount > 1");
    $result['Active'] = $q->Fields["Total"];
    $q = new TQuery("SELECT COUNT(*) as Total FROM UserAgent WHERE ClientID IS NULL AND AgentID IN (SELECT UserID FROM Usr WHERE UserID IN (SELECT DISTINCT UserID FROM Account) AND LogonCount > 1)");
    $result['ActiveAgents'] = $q->Fields["Total"];
    $result['ActiveTotal'] = $result["Active"] + $result["ActiveAgents"];
    $result['lastLogged'] = (new TQuery("select count(*) as c from Usr where date_add(LastLogonDateTime, interval 24 hour) > now()"))->Fields['c'];
    $q = new TQuery("select count(AccountID) as Disabled from Account where Disabled = 1");
    $result['DisabledAccounts'] = $q->Fields['Disabled'];
    $q = new TQuery("select count(*) as Users from Usr where AccountLevel = " . ACCOUNT_LEVEL_AWPLUS);
    $result['AWPlus'] = $q->Fields['Users'];
    $q = new TQuery("select count(distinct UserID) as Users from (select c.UserID 
		from Cart c 
		join CartItem ci on c.CartID = ci.CartID
		where c.PayDate is not null and c.PaymentType is not null
		group by c.CartID
		having sum((100 - ci.Discount) * ci.Cnt * ci.Price) > 0) a");
    $result['EverPaid'] = $q->Fields['Users'];
    $q = new TQuery("SELECT COUNT(*) AS Total FROM Usr WHERE Subscription is not null and AccountLevel = " . ACCOUNT_LEVEL_AWPLUS);
    $result['Subscribers'] = $q->Fields["Total"];

    return $result;
}

function registrationsLastMonthGraph()
{
    $objRS = new TQuery("select DATE(CreationDateTime) as SortDate,  DATE_FORMAT(DATE(CreationDateTime),'%e') as RegDate, COUNT(DATE(CreationDateTime)) as RegDateCount from Usr GROUP BY RegDate, SortDate HAVING SortDate >  DATE_SUB(NOW(), INTERVAL 1 MONTH) ORDER BY SortDate;");
    $chartValuesY = [];
    $chartValuesX = [];
    $dataLables = [];
    $axisY = [];
    $i = 0;

    while (!$objRS->EOF) {
        $chartValuesY[] = $objRS->Fields['RegDateCount'];
        $chartValuesX[] = $objRS->Fields['RegDate'];
        $dataLables[] = "t" . $objRS->Fields['RegDateCount'] . ",001bc0,0,$i,15";
        $i++;
        $objRS->Next();
    }

    for ($i = 0; $i <= max($chartValuesY); $i = $i + 100) {
        $axisY[] = $i;
    }
    $maxVal = max($chartValuesY);
    $step = number_format(100 / $maxVal, 2, '.', '');

    return "http://chart.apis.google.com/chart?cht=bvg&chs=750x300&chbh=15&chd=t:" . implode(",", $chartValuesY) . "&chds=0,$maxVal&chco=c6d9fd&chxt=x,y&chxl=0:|" . implode("|", $chartValuesX) . "|1:|" . implode("|", $axisY) . "&chg=0,$step&chm=" . implode("|", $dataLables) . "&chtt=Registrations%20-%20last%20month";
}

function monthlyRegistrationsGraph()
{
    $monthCounter = new TQuery("select COUNT(DISTINCT DATE_FORMAT(DATE(CreationDateTime),'%Y-%m')) as MonthCounter
	from Usr");
    $monthsToTrack = 24;
    $startingMonth = $monthCounter->Fields["MonthCounter"] - $monthsToTrack;

    $objRS = new TQuery("select DATE_FORMAT(DATE(CreationDateTime),'%Y-%m') as CreationDateTime, DATE_FORMAT(DATE(CreationDateTime),'%M, %y') as RegDate, COUNT(DATE(CreationDateTime)) as RegDateCount
	from Usr
	GROUP BY RegDate
	ORDER BY CreationDateTime
	LIMIT $startingMonth, $monthsToTrack");

    $chartValuesY = [];
    $chartValuesX = [];
    $dataLables = [];
    $axisY = [];
    $axisX = [1, 2, 3, 4, 5, 6];
    $i = 0;

    while (!$objRS->EOF) {
        $chartValuesY[] = $objRS->Fields['RegDateCount'];

        if ($i % 2 == 0) {
            $chartValuesX[] = $objRS->Fields['CreationDateTime'];
        } else {
            $chartValuesX[] = "";
        }
        $dataLables[] = "t" . $objRS->Fields['RegDateCount'] . ",001bc0,0,$i,15";
        $i++;
        $objRS->Next();
    }
    $maxVal = max($chartValuesY);

    for ($i = 0; $i <= $maxVal; $i = $i + floor($maxVal / 10)) {
        $axisY[] = $i;
    }
    $step = number_format(100 / max($axisY), 2, '.', '');
    $step = 10;

    return "http://chart.apis.google.com/chart?cht=lc&chs=900x300&chbh=10&chd=t:" . implode(",", $chartValuesY) . "&chds=0,$maxVal&chco=c6d9fd&chxt=x,y&chxl=0:|" . implode("|", $chartValuesX) . "|1:|" . implode("|", $axisY) . "&chg=0,$step&chm=" . implode("|", $dataLables) . "&chtt=Monthly%20registrations";
}

function showQueryAsTable($q, $title, $style, $formatFields = null)
{
    echo "<table class='detailsTable' cellpadding='3' style='$style'>";
    $headerStyle = "font-weight: bold; text-align: center; background-color: #dddddd;";

    if ($q->EOF) {
        echo "<tr><td style='{$headerStyle}'>{$title}</td></tr>";
        echo "<tr><td>No data</td></tr>";
    } else {
        echo "<tr><td colspan='" . count($q->Fields) . "' style='{$headerStyle}'>{$title}</td></tr>";
        echo "<tr>";

        foreach ($q->Fields as $key => $value) {
            $key = preg_replace("/([a-z])([A-Z])/ms", "\\1 \\2", $key);
            echo "<td style='font-weight: bold;'>$key</td>";
        }
        echo "</tr>";

        while (!$q->EOF) {
            if (isset($formatFields)) {
                $formatFields($q->Fields);
            }
            echo "<tr>";

            foreach ($q->Fields as $key => $value) {
                echo "<td>$value</td>";
            }
            echo "</tr>";
            $q->Next();
        }
    }
    echo "</table>";
}

function calcOneCards($startDate, $endDate, &$oneCardsCount, &$oneCardsFee, $where = '')
{
    $oneCardsQuery = new TQuery(
        "select
                sum(case when ci.TypeID = " . CART_ITEM_ONE_CARD_SHIPPING . " then ci.UserData else 0 end) as OneCardsCount
			from
				Cart c
				join CartItem ci on c.CartID = ci.CartID
				join Usr u on c.UserID = u.UserID
			where
				ci.TypeID = " . CART_ITEM_ONE_CARD_SHIPPING . " and ci.UserData > 0
				and c.PayDate >= " . $startDate . "
				and c.PayDate < " . $endDate . "
				and c.UserID is not null
 				and c.PayDate is not null
 				{$where}
    ");
    $oneCardsCount = intval($oneCardsQuery->Fields['OneCardsCount']);
    $oneCardsFee = $oneCardsCount * 1.5;
}

function getPaymentsSql(?int $startDate = null, ?int $endDate = null, ?array $selectedPaymentTypes = null, ?string $where = null)
{
    if ($startDate === null) {
        $startDate = strtotime("1980-01-01");
    }

    if ($endDate === null) {
        $endDate = strtotime("+1 year");
    }

    if ($selectedPaymentTypes === null) {
        $selectedPaymentTypes = array_keys(getPaymentTypesForReports());
    }

    $at201Types = \AwardWallet\MainBundle\Entity\CartItem\At201Items::getTypes();

    if (false !== ($nullPosition = array_search(null, $selectedPaymentTypes, true))) {
        $paymentTypes = ' OR c.PaymentType IS NULL ';
        unset($selectedPaymentTypes[$nullPosition]);
    }
    $paymentTypes = 'c.PaymentType in(' . implode(", ", $selectedPaymentTypes) . ')' . ($paymentTypes ?? '');

    return "select
		c.PayDate,
		c.CartID,
		c.PaymentType,
		c.CouponID,
		c.CouponCode,
		co.Discount,
		c.FirstName,
		c.LastName,
		sum(ci.Price * ci.Cnt * ((100-ci.Discount)/100)) as Price,
		max(case when ci.UserData = " . CART_FLAG_RECURRING . " then 'Recurring' else null end) as Recurring,
		max(case when ci.TypeID in (" . implode(', ', $at201Types) . ") then 1 else 0 end) as at201type
	from
		Cart c
		join CartItem ci on c.CartID = ci.CartID
		left outer join Coupon co on c.CouponID = co.CouponID
	where
		c.PayDate >= '" . date("Y-m-d H:i:s", $startDate) . "'
		and c.PayDate < '" . date("Y-m-d H:i:s", $endDate) . "'
		and (ci.Price * ci.Cnt * ((100-ci.Discount)/100)) <> 0
		and (" . $paymentTypes . ")
		and c.CartID not in (
		    select c.CartID 
		    from Cart c 
		    join CartItem ci on c.CartID = ci.CartID
		    where ci.TypeID = " . CART_ITEM_BOOKING . "
		    and c.PayDate >= '" . date("Y-m-d H:i:s", $startDate) . "'
		    and c.PayDate < '" . date("Y-m-d H:i:s", $endDate) . "'
		)
		and ci.ScheduledDate is null
		{$where}
	group by
		c.PayDate,
		c.CartID,
		c.PaymentType,
		c.CouponID,
		c.CouponCode,
		co.Discount,
		c.FirstName,
		c.LastName
	order by
		c.PayDate";
}

function getPaymentTypesForReports()
{
    global $arPaymentTypeName;
    $names = $arPaymentTypeName;
    $names[PAYMENTTYPE_STRIPE_INTENT] = "Stripe";
    $names[PAYMENTTYPE_CREDITCARD] = "PayPal (Credit Card)";
    $names[\AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_STRIPE] = "Stripe (old)";

    return array_intersect_key($names, [
        PAYMENTTYPE_ANDROIDMARKET => null,
        PAYMENTTYPE_APPSTORE => null,
        PAYMENTTYPE_CREDITCARD => null,
        \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_STRIPE => null,
        PAYMENTTYPE_STRIPE_INTENT => null,
        PAYMENTTYPE_PAYPAL => null,
        PAYMENTTYPE_BITCOIN => null,
    ]);
}
