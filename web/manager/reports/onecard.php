<?
$schema = "onecard";
require "../start.php";
require_once( "$sPath/kernel/TForm.php" );

drawHeader("OneCards");

require __DIR__ . "/paymentsCommon.php";

$objForm = new TForm(array(
	"Total" => array(
		"Type" => "html",
		"HTML" => getStats(),
	),
	"StartDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, mktime(0, 0, 0, date("m") - 1, 1, date("Y"))),
	),
	"EndDate" => array(
		"Type" => "date",
		"Value" => date(DATE_FORMAT, time() + SECONDS_PER_DAY),
	),
	"Button" => array(
		"Type" => "html",
		"Caption" => "",
		"HTML" => getTodayButtons(),
	),
));
$objForm->SubmitButtonCaption = "Show OneCard stats";
$objForm->ButtonsAlign = "left";

if($objForm->IsPost && $objForm->Check()){
	echo $objForm->HTML();
	$objForm->CalcSQLValues();
	echo "<div>Displaying OneCards {$objForm->Fields["StartDate"]["Value"]} <= PayDate < {$objForm->Fields["EndDate"]["Value"]}</div><br>";
	$q = new TQuery("
	select
		Date(c.PayDate) as PayDate,
		sum(ci.Cnt) as CreditsReceived,
		sum(cio.Cnt) as CreditsSpent,
		sum(cio.UserData) as OrderedCards
	from
		Cart c
		left outer join CartItem ci on c.CartID = ci.CartID and ci.TypeID = ".CART_ITEM_ONE_CARD."
		left outer join CartItem cio on c.CartID = cio.CartID and cio.TypeID = ".CART_ITEM_ONE_CARD_SHIPPING."
	where
		c.PayDate >= ".$objForm->Fields["StartDate"]["SQLValue"]."
		and c.PayDate < ".$objForm->Fields["EndDate"]["SQLValue"]."
	group by
		Date(c.PayDate)
	order by
		Date(c.PayDate)
	");
	if($q->EOF)
		$Interface->DrawMessage("There are no transactions for this dates", "warning");
	else{
		echo "<table class=stats cellpadding=0 cellspacing=0 border=0>";
		echo "<tr class=head>";
		foreach($q->Fields as $sField => $sValue)
			echo "<td>".NameToText($sField)."</td>";
		$nFieldCount = count($q->Fields);
		echo "</tr>";
		$nTotalCredits = 0;
		$nTotalOrdered = 0;
		$nTotalCards = 0;
		while(!$q->EOF){
			echo "<tr>";
			foreach($q->Fields as $sField => $sValue)
				echo "<td>$sValue</td>";
			$nTotalCredits += $q->Fields["CreditsReceived"];
			$nTotalOrdered += $q->Fields["CreditsSpent"];
			$nTotalCards += $q->Fields["OrderedCards"];
			echo "</tr>";
			$q->Next();
		}
		echo "<tr class=total><td>&nbsp;</td>";
		echo "<td>" . number_format($nTotalCredits) . "</td>";
		echo "<td>" . number_format($nTotalOrdered) . "</td>";
		echo "<td>" . number_format($nTotalCards) . "</td>";
		echo "</tr>";
		echo "</table>";
	}
}
else
	echo $objForm->HTML();

function getStats(){
	$q = new TQuery("select
		sum(ci.Cnt) as CreditsReceived,
		sum(cio.Cnt) as CreditsSpent
	from
		Cart c
		left outer join CartItem ci on c.CartID = ci.CartID and ci.TypeID = ".CART_ITEM_ONE_CARD."
		left outer join CartItem cio on c.CartID = cio.CartID and cio.TypeID = ".CART_ITEM_ONE_CARD_SHIPPING."
	where
		c.PayDate is not null");
	return "Credits received: " . number_format($q->Fields['CreditsReceived']) . ", spent: " . number_format($q->Fields['CreditsSpent']) . ", difference: <b>"
		   . number_format($q->Fields['CreditsReceived'] - $q->Fields['CreditsSpent']) . "</b>";
}

?>
