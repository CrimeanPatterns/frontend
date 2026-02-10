<?
require( "../kernel/public.php" );
require_once($sPath."/kernel/TForm.php");
require_once($sPath."/lib/cart/public.php");
require_once($sPath."/onecard/common.php");
AuthorizeUser();
if (isGranted("SITE_ND_SWITCH") && SITE_MODE == SITE_MODE_BUSINESS) {
    Redirect("/");
}

# begin determining menus
require($sPath."/design/topMenu/main.php");
require($sPath."/design/leftMenu/award.php");
if (SITE_MODE == SITE_MODE_PERSONAL){
	$topMenu['My Balances']['selected'] = false;
}
$topMenu['OneCard']['selected'] = true;
NDSkin::setLayout("@AwardWalletMain/Layout/onecard.html.twig");
getSymfonyContainer()->get("aw.widget.onecard_menu")->setActiveItem('history');
# end determining menus

if(isset($_SESSION['UserFields']['DateFormat'])){
	$dateFormat = DateFormats($_SESSION['UserFields']['DateFormat']);
}
else
    $dateFormat = DateFormats(1);

$sTitle = "Awardwallet OneCard Order History";
$bSecuredPage = False;

unset($othersMenu);
$leftMenu = array(
	'Place New Order' => array(
		'caption'	=> 'Place New Order',
		'path'		=> '/onecard',
		'selected'	=> false
	),
	'Order History' => array(
		'caption'	=> 'Order History',
		'path'		=> '/onecard/history.php',
		'selected'	=> true
	)
);

require( "$sPath/design/header.php" );
?>
<link rel="stylesheet" type="text/css" href="/design/onecard.css?v=<?=FILE_VERSION?>"/>

<script>
function checkCredits(cartID, userAgentID, oneCardLeft){
	if (oneCardLeft < 1){
		showMessagePopup('info', 'Not enough OneCard Credits', 'To place the same order again you need to have at least 1 credit, currently you have 0 credits, please get more credits <a href="/user/pay.php">here</a>.');
	}
	else{
		showCardOrder(cartID, userAgentID, 0);
	}
}

function showCardOrder(cartID, userAgentID, readOnly){
	var form = document.forms['editor_form'];
	document.getElementById('cartID').value = cartID;
	document.getElementById('userAgentID').value = userAgentID;
	document.getElementById('readOnly').value = readOnly;
	form.submit();
}
</script>

<?
$q = new TQuery("SELECT CartID, UserAgentID, FullName, OrderDate, PrintDate, State, count(OneCardID) as NumberOfCards
				FROM OneCard
				WHERE UserID = {$_SESSION['UserID']}
				GROUP BY CartID, UserAgentID, FullName, OrderDate, PrintDate, State");

if ( $q->EOF ){
	$Interface->DrawMessageBox("You have no OneCard orders", "info"); /*checked*/
}
else{
    ?>
    <table cellspacing="0" cellpadding="0" class="roundedTable" style="width: 100%;">
		<tr class="afterTop">
			<td class="tabHeader" colspan="6">
				<div class="icon"><div class="left"></div></div>
				<div class="caption">AwardWallet OneCard Order History</div>
			</td>
		 </tr>
		<tr class="head afterGroup">
			<td class="c1head">
				<div class="icon"><div class="inner"></div></div>
				<div class="caption">Card for</div>
			</td>
			<td class="leftDots noWrap">Number of cards</td>
			<td class="leftDots">Order Date</td>
			<td class="leftDots">Print Date</td>
			<td class="leftDots">Status</td>
			<td class="leftDots"></td>
		 </tr>
    <?
	$lastRowKind = "Head";
    while(!$q->EOF){
		$classes = "after".$lastRowKind;
		if(($q->Position % 2) == 0){
			$classes .= " grayBg";
			$lastRowKind = "Gray";
		}
		else{
			$classes .= " whiteBg";
			$lastRowKind = "White";
		}
	//	$sViewLink = '<a class="checkLink" href="#" onclick="showCardOrder('.$q->Fields["CartID"].','.$q->Fields["UserAgentID"].',1)">View</a>';
        $sViewLink = '<a class="checkLink" href="/onecard/designer.php?cartID='.$q->Fields["CartID"].'&userAgentID='.$q->Fields["UserAgentID"].'">View</a>';
		$sDeleteLink = "<a class='checkLink' href='/onecard/cancelOrder.php?CartID=".$q->Fields["CartID"]."&UserAgentID=".$q->Fields["UserAgentID"]."' title=Delete
		onclick=\"return window.confirm('Are you sure you want to delete this order?')\">Delete</a>";
		$sReOrderLink = '<a class="checkLink" href="#" onclick="checkCredits('.$q->Fields["CartID"].','.$q->Fields["UserAgentID"].','.$topMenu['OneCard']['count'].')">Re-Order</a>';
        ?>
        <tr class="<?=$classes?>">
			<td class="c1">
				<? if($q->Position == 1) { ?>
				<div class="icon"><div class="inner"></div></div>
				<? } ?>
				<div class="caption"><?=$q->Fields["FullName"]?></div>
			</td>
			<td class="pad leftDots"><?
			echo $q->Fields['NumberOfCards'];
			?></td>
			<td class="pad leftDots"><?
			echo date($dateFormat['date'], strtotime($q->Fields['OrderDate']));
			?></td>
			<td class="pad leftDots"><?
				if (isset($q->Fields['PrintDate']))
					echo date($dateFormat['date'], strtotime($q->Fields['PrintDate']));
			?></td>
			<td class="pad leftDots"><?
				switch ($q->Fields['State']){
					case '1':
						echo 'New';
						break;
					case '2':
						echo 'Printing';
						break;
					case '3':
						echo 'Printed';
						break;
					case '4':
						echo 'Broken';
						break;
					case '5':
						echo 'Deleted';
						break;
					case '6':
						echo 'Refunded';
						break;
					default:												
						break;
				}
			?></td> 			
            <td class="pad leftDots noWrap manage" style="text-align: center;">
				<?
				$editLinks = array();
				$editLinks[] = $sViewLink;
				if ($q->Fields['State'] == '1'){
					$editLinks[] = $sDeleteLink;
				}
				else{
					$editLinks[] = $sReOrderLink;
				}
				echo $Interface->getEditLinks($editLinks);
				?>
			</td>
        </tr>
        <?
        $q->Next();
    }	?>
	
		<tr class='after<?=$lastRowKind?>'>
			<td colspan=6 class="whiteBg topBorder" style="height: 1px;"></td>
		</tr>
    </table>
	<br>
	<form method='post' name="editor_form" action="/onecard/designer.php">
		<input type="hidden" id="readOnly" name="readOnly">
		<input type="hidden" id="cartID" name="cartID">
		<input type="hidden" id="userAgentID" name="userAgentID">
	</form>
    <?
}

require( "$sPath/design/footer.php" );
?>
