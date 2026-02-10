<?

class TNewsList extends TBaseNewsList{
	function DrawRow(){
		global $Connection, $Interface;
		$objRS = &$this->Query;
		$date = $Connection->SQLToDateTime($this->OriginalFields["NewsTime"]);
		$objRS->Fields["NewsTime"] = date(DATE_LONG_FORMAT, $date);
		echo '<tr>
	<td style="padding-left: 5px;" height="30">
<span class="news"><strong>'.$objRS->Fields["NewsTime"].'</strong> - '.$objRS->Fields["BodyText"].'</span>
	</td>
</tr>';
	}
}

?>