<?
$schema = "sentMail";
require "../start.php";
drawHeader("Sent mail");

// translate old kinds to new
$arKindTranslate = array(
);

// description of new kinds
$arStatInfo = array(
	'booking_invoice'                                            => array(
		'Kind'        => 'New invoice for your award booking request',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'accept_booking_invoice'                                     => array(
		'Kind'        => 'Booking request invoice has been paid',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'booking_respond'                                            => array(
		'Kind'        => 'Booker has responded to your booking request',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'booking_request_refund'                                     => array(
		'Kind'        => 'Refund request',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'booking_change_status_booker'                               => array(
		'Kind'        => 'The status of your booking request has changed',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'booking_share_accounts'                                     => array(
		'Kind'        => 'Request to share your accounts',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'new_booking_request_to_booker'                              => array(
		'Kind'        => 'New Booking request has been received',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
	'new_booking_request_to_user'                                => array(
		'Kind'        => 'Details of you booking request',
		/*checked*/
		'Description' => '',
		'Image'       => '',
	),
);

?>
<style>
    table.stats {
        border-collapse: collapse;
    }

    table.stats td {
        border: 1px solid gray;
        padding: 3px;
    }

    table.stats tr.head td, table.stats tr.total td {
        background-color: yellow;
        font-weight: bold;
    }
</style>
<?

$objForm = new TForm(array(
						  "StartDate" => array(
							  "Type"  => "date",
							  "Value" => date(DATE_FORMAT, strtotime("-1 month")),
						  ),
						  "EndDate"   => array(
							  "Type"  => "date",
							  "Value" => date(DATE_FORMAT, time()),
						  ),
					 ));
$objForm->SubmitButtonCaption = "Calculate email volume";

if ($objForm->IsPost && $objForm->Check()) {
	$objForm->CalcSQLValues();
	if (strtotime(trim($objForm->Fields["StartDate"]["SQLValue"], "'")) > strtotime(trim($objForm->Fields["EndDate"]["SQLValue"], "'"))) {
		$temp = $objForm->Fields["StartDate"]["Value"];
		$objForm->Fields["StartDate"]["Value"] = $objForm->Fields["EndDate"]["Value"];
		$objForm->Fields["EndDate"]["Value"] = $temp;
	}
	echo $objForm->HTML();
	$objForm->CalcSQLValues();
	echo "<div>Displaying emails {$objForm->Fields["StartDate"]["Value"]} <= Sent Date <= {$objForm->Fields["EndDate"]["Value"]}</div><br>";
	$q = new TQuery("
		select
			Kind,
			sum(Messages) as Messages
		from
			EmailStat
		where
			StatDate >= " . $objForm->Fields["StartDate"]["SQLValue"] . "
			and StatDate <= " . $objForm->Fields["EndDate"]["SQLValue"] . "
		group by
			Kind
		order by
			sum(Messages) desc
	");
	$nTotal = 0;
	while (!$q->EOF) {
		$q->Fields['Messages'] = intval($q->Fields['Messages']);
		$nTotal += $q->Fields['Messages'];
		if (isset($arStatInfo[$q->Fields['Kind']])) {

			$kind = $q->Fields['Kind'];
			if (isset($arStatInfo[$kind]['Messages']))
				$arStatInfo[$kind]['Messages'] += $q->Fields['Messages'];
			else
				$arStatInfo[$kind]['Messages'] = $q->Fields['Messages'];

		} elseif (isset($arKindTranslate[$q->Fields['Kind']])) {

			$kind = $arKindTranslate[$q->Fields['Kind']];
			if (isset($arStatInfo[$kind]['Messages']))
				$arStatInfo[$kind]['Messages'] += $q->Fields['Messages'];
			else
				$arStatInfo[$kind]['Messages'] = $q->Fields['Messages'];

		} else {

			$arStatInfo[$q->Fields['Kind']] = array(
				'Kind'     => $q->Fields['Kind'],
				'Messages' => $q->Fields['Messages'],
			);
		}
		$q->Next();
	}
	uasort($arStatInfo, function ($a, $b) {
		return ArrayVal($b, 'Messages', 0) - ArrayVal($a, 'Messages', 0);
	});
	echo "<table class=stats cellpadding=0 cellspacing=0 border=0>";
	echo "<tr class=head><td>Kind</td><td>Description</td><td>Messages</td></tr>";
	foreach ($arStatInfo as $row) {
		$kind = ArrayVal($row, 'Kind', '&nbsp;');
		$desc = ArrayVal($row, 'Description', '&nbsp;');
		$msgs = ArrayVal($row, 'Messages', '&nbsp;');
		$image = ArrayVal($row, 'Image', '');
		if (!empty($image))
			$kind = "<a href='$image' target='_blank'>$kind</a>";
		echo "<tr><td>$kind</td><td>$desc</td><td>$msgs</td></tr>";
	}
	echo "<tr class=total><td>&nbsp;</td><td>&nbsp;</td><td>$nTotal</td></tr>";
	echo "</table>";
} else
	echo $objForm->HTML();

echo "<br>";

drawFooter();
