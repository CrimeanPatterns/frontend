<?
class TAdminCartList extends TBaseAdminCartList{

    function FormatFields($output = "html"){
		global $Connection;
		parent::FormatFields($output);
		$total = 0;
		$this->Query->Fields["Order"] = "<a href=\"/manager/cart/contents.php?CartID={$this->OriginalFields['CartID']}\">" . implode("<br/>", array_map(
			function($row) use (&$total){
                if ($row["ScheduledDate"] === null) {
                    $total += $row['Price'] * $row['Cnt'] * ((100 - $row['Discount']) / 100);
                }
				if($row['TypeID'] == CART_ITEM_ONE_CARD_SHIPPING)
					return "OneCard ordered: ".$row["Cnt"];
				elseif($row['TypeID'] == CART_ITEM_BOOKING)
					return strip_tags($row["Name"]).": ".$row["Cnt"];
				else
					return strip_tags(preg_replace("/\([^\)]+\)/ims", "", $row["Name"] . ' ' . $row['Description'])).": ".$row["Cnt"];
			},
			SQLToArray("select * from CartItem where CartID = {$this->OriginalFields["CartID"]}", "CartItemID", "Name", true)
		)) . "</a>
            " . (!empty($this->Query->Fields['BillingTransactionID']) ? "<br/>" . $this->Query->Fields['BillingTransactionID'] : "") . "
            " . (!empty($this->Query->Fields['Comments']) ? "<br/>" . $this->Query->Fields['Comments'] : "") . "
        ";
		$this->Query->Fields['Total'] = '$' . round($total, 2);
		$this->Query->Fields["PayDate"] = date("m/d/Y", $Connection->SQLToDateTime($this->OriginalFields["PayDate"]));

        $this->Query->Fields['UserRegistrationDate'] = empty($this->OriginalFields['CreationDateTime'])
            ? ''
            : date("m/d/Y", $Connection->SQLToDateTime($this->OriginalFields['CreationDateTime']));
        if ($this->Query->Fields["PayDate"] == $this->Query->Fields['UserRegistrationDate']) {
            $this->Query->Fields['UserRegistrationDate'] = '<span style="color: #999">' . $this->Query->Fields['UserRegistrationDate'] . '</span>';
        }

        $paymentStats = getSymfonyContainer()->get(\AwardWallet\MainBundle\Entity\Repositories\UsrRepository::class)->getPaymentStatsByUser((int) $this->Query->Fields['UserID']);
        $this->Query->Fields['PayingUser'] = $paymentStats['LifetimeContribution'] > 0 ? '<span title="' . $paymentStats['PaidOrders'] . ' paid orders">$' . $paymentStats['LifetimeContribution'] . '</span>' : '';
	}

	//return edit links html
	function GetEditLinks()
	{
		$arFields = &$this->Query->Fields;
		$s = "";
		if(!$this->ReadOnly){
			if( $this->AllowDeletes && !$this->MultiEdit )
				$s .= " | <input type=hidden name=sel{$arFields[$this->KeyField]} value=\"\">\n<a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel{$arFields[$this->KeyField]}.value='1'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
		}
		$s .= " <a href='/manager/cart/contents.php?CartID={$arFields['CartID']}'>Contents</a>";
		return $s;
	}

}
