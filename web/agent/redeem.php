<?php
require "../kernel/public.php";

require_once "$sPath/kernel/siteFunctions.php";

AuthorizeUser();

// begin determining menus
require $sPath . "/design/topMenu/main.php";

require $sPath . "/design/leftMenu/trips.php";
// end determining menus

NDSkin::setLayout("@AwardWalletMain/Layout/common.html.twig");

$sTitle = "Redeem bonus points";

require "$sPath/design/header.php";

// AWB = Rate * ProviderMiles
$rates = SQLToSimpleArray('SELECT * FROM `BonusConversionProvider` WHERE `Enabled` = 1', 'ProviderID', true);

if (empty($rates)) {
    $Interface->DrawMessageBox("There is no available options to covert AwardWallet Bonus points", "error");

    require "$sPath/design/footer.php";

    return;
}

class BonusConverter
{
    public const AW_REVENUE_MULTIPLIER = '2';
    public const AW_MULTIPLIER = '25';

    public const BC_SCALE = 7;

    public static function bonusToMiles($awBonus, $airlineFixedFee, $costPerMile)
    {
        return bcdiv(
            bcsub(
                bcdiv(
                    $awBonus,
                    bcmul(
                        self::AW_REVENUE_MULTIPLIER,
                        self::AW_MULTIPLIER,
                        self::BC_SCALE
                    ),
                    self::BC_SCALE
                ),
                $airlineFixedFee,
                self::BC_SCALE
            ),
            $costPerMile,
            self::BC_SCALE
        );
    }

    public static function milesToBonus($miles, $airlineFixedFee, $costPerMile, $scale = null)
    {
        return bcmul(
            self::AW_MULTIPLIER,
            bcmul(
                self::AW_REVENUE_MULTIPLIER,
                bcadd(
                    $airlineFixedFee,
                    bcmul(
                        $miles,
                        $costPerMile,
                        self::BC_SCALE
                    ),
                    self::BC_SCALE
                ),
                self::BC_SCALE
            ),
            self::BC_SCALE
        );
    }

    public static function convertRates($awBonus, array &$rateData)
    {
        $miles = self::bonusToMiles($awBonus, $rateData['FixedFee'], $rateData['OneMileCost']);

        if (bccomp($rateData['MinMiles'], $miles) !== 1) {
            $rateData['Miles'] = floor(bcsub($miles, bcmod($miles, $rateData['StepMiles']), self::BC_SCALE)); // available miles
            $rateData['Points'] = ceil(self::milesToBonus($rateData['Miles'], $rateData['FixedFee'], $rateData['OneMileCost'])); // points needed to buy available miles
        }
    }
}

$providers = [];

foreach ($rates as $rateData) {
    $providers[] = $rateData['ProviderID'];
}

$providerFilter = implode(',', $providers);

// get Accounts
GetAgentFilters($_SESSION['UserID'], "All", $accountFilter, $couponFilter, false, true);
$accountsQuery = new TQuery(AccountsSQL($_SESSION['UserID'], $accountFilter, "0 = 1", " and p.ProviderID IN({$providerFilter}) order by UserName", " and 0 = 1", "All"));

$accountsByProvider = [];

// $accountsList = array();
foreach ($accountsQuery as $account) {
    $properties = SQLToArray("
		SELECT
			pp.Kind,
			ap.Val
		FROM Account a
		JOIN AccountProperty ap on a.AccountID = ap.AccountID
		JOIN ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
		WHERE
			a.AccountID = {$account['ID']} AND
			pp.Kind IS NOT NULL", 'Kind', 'Val');
    $account['PropertyAccountNumber'] = ArrayVal($properties, PROPERTY_KIND_NUMBER, 'n/a');
    $account['PropertyAccountName'] = ArrayVal($properties, PROPERTY_KIND_NAME, 'n/a');
    $accountsByProvider[$account['ProviderID']]['UserAgents'][$account['UserID']][] = $account;
    $accountsByProvider[$account['ProviderID']]['Accounts'][$account['ID']] = $account;
}

$totalBonus = getSymfonyContainer()->get('aw.referral_income_manager')->getTotalReferralBonusBalanceByUser($_SESSION['UserID']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isValidFormToken()) {
    requirePasswordAccess();
    $providerID = intval(ArrayVal($_POST, 'provider'));
    $accountID = intval(ArrayVal($_POST, 'accountID'));
    $rateData = ArrayVal($rates, $providerID);

    if (!empty($rateData) && isset($accountsByProvider[$providerID]['Accounts'][$accountID])) {
        BonusConverter::convertRates($totalBonus, $rateData);

        if (isset($rateData['Miles'])) {
            $purchaseLink = '';

            if (isset($rateData['PurchaseLink']) && !empty($rateData['PurchaseLink'])) {
                $purchaseLink = "Purchase link: {$rateData['PurchaseLink']}";
            }

            if (isset($rateData['AffiliateLink']) && !empty($rateData['AffiliateLink'])) {
                $purchaseLink = "Affiliate link: {$rateData['AffiliateLink']}";
            }
            $account = $accountsByProvider[$providerID]['Accounts'][$accountID];
            $Connection->Execute("
				INSERT INTO BonusConversion(
					Airline,
					Points,
				  	Miles,
				  	CreationDate,
				  	Processed,
				  	UserID,
				  	AccountID
				)
				VALUES(
				'" . $rateData['Name'] . "',
					{$rateData['Points']},
			  		{$rateData['Miles']},
				  	NOW(),
				  	0,
				  	{$_SESSION['UserID']},
				  	{$accountID}
				)");
            mailTo(
                SUPPORT_EMAIL,
                "request to convert bonus points",
                "
UserID: {$_SESSION['UserID']}
Provider: {$rateData['Name']}
AccountID: {$accountID}
Login: {$account['Login']}
AccountNumber: {$account['PropertyAccountNumber']}
Name: {$account['PropertyAccountName']}

AW bonus deducted: {$rateData['Points']}
Miles / Points requested: {$rateData['Miles']}

Email: {$_SESSION['Email']}

Schema: https://awardwallet.com/manager/list.php?Schema=BonusConversion

{$purchaseLink}",
                EMAIL_HEADERS);

            $Interface->DrawMessageBox("Thank you. Your request was submitted. The miles/points will be posted to your account soon.", "success");
        } else {
            $Interface->DrawMessageBox("You don't have enough AwardWallet Bonus points", "error");
        }
    } else {
        DieTrace('BonusConversion: Invalid form data', false);
        $Interface->DrawMessageBox("We cannot find requested account", "error");
    }
} else {
    ?>
<p>
	We can convert your AwardWallet Bonus points to airline miles or hotel points with following rates.
	Currently you have <b><?php echo number_format_localized($totalBonus, 0); ?> AwardWallet Bonus points</b>. Select a loyalty program, where you want to redeem your bonus points:
</p>
<form method="post" id="redeemForm">
<input type='hidden' name='FormToken' value='<?php echo GetFormToken(); ?>'>
<table cellspacing="0" cellpadding="0" border="0" class="roundedTable" width="100%">
	<tr class="head">
		<td class="c1head" style="width: 1%;"></td>
		<td class="leftDots">Program</td>
		<td class="leftDots">AwardWallet Bonus points</td>
		<td class="leftDots">Miles / Points</td>
	</tr>
	<?php foreach ($rates as $providerID => $rateData) {
	    if (isset($rateData['getDataCall']) && is_callable($rateData['getDataCall'])) {
	        $newData = Cache::getInstance()->get('RedemptionData_GEN3_' . $providerID);

	        if ($newData === false) {
	            $newData = $rateData['getDataCall']();
	            $expiration = 3600;
	            Cache::getInstance()->set('RedemptionData_GEN3_' . $providerID, $newData, $expiration);
	        }

	        if (is_array($newData)) {
	            $rateData = array_merge($rateData, $newData);
	        }
	    }
	    BonusConverter::convertRates($totalBonus, $rateData);
	    $disabled = false;

	    if (!isset($rateData['Miles'])) {
	        $rateData['Miles'] = $rateData['MinMiles'];
	        $rateData['Points'] = ceil(BonusConverter::milesToBonus($rateData['Miles'], $rateData['FixedFee'], $rateData['OneMileCost']));
	        $disabled = true;
	    }
	    $rateData['Attributes'] = "data-provider-id='{$rateData['ProviderID']}'";
	    // if(isset($accountsByProvider[$rateData['ProviderID']]))
	    //	$rateData['Attributes'] .= (count($accountsByProvider[$rateData['ProviderID']]['Accounts']) == 1) ? " data-accounts='1'" : '';
	    ?>
	<tr class="afterWhite whiteBg<?php echo (isset($disabled) && $disabled) ? ' disable' : ''; ?>">
		<td class="c1" style="padding: 10px;"><input type="radio" name="provider" value="<?php echo $providerID; ?>" <?php echo (isset($disabled) && $disabled) ? ' disabled' : ''; ?> <?php echo $rateData['Attributes']; ?>/></td>
		<td class="pad leftDots"><?php echo $rateData['Name']; ?></td>
		<td class="pad leftDots"><?php echo formatFullBalance($rateData['Points'], null, null); ?></td>
		<td class="pad leftDots"><?php echo formatFullBalance($rateData['Miles'], null, null); ?></td>
	</tr>
	<?php } ?>
	<tr><td class="c1 lastRow" colspan="4"></td></tr>
</table>
<br>
<?php
echo $Interface->DrawButton("Submit redemption request", "style='width: 240px;' onclick=\"redeem(); return false;\"");
    $Interface->DrawBeginBox('id="redeemedAccountPopup"', 'Select Account', true, "popupWindow");

    ?>
	<div style="padding: 20px;">
				<div class="question">Please specify the account number you wish miles/points to be deposited to</div>
				<table cellpadding="0" cellspacing="0" border="0" class="formRows" style="width: 100%;">
					<tr>
						<td class="caption"><nobr>Account:</nobr></td>
						<td style="text-align: right;">
							<?php
                                foreach ($accountsByProvider as $providerID => $providerData) {
                                    echo "
								<div class='styled-select' style='display: none;'>
								<select id='provider_{$providerID}' style='width: 350px;'>
									<option value=''>Select account...</option>";

                                    foreach ($providerData['UserAgents'] as $userAccounts) {
                                        $lastUserName = '';

                                        foreach ($userAccounts as $account) {
                                            $userName = $account['UserName'];

                                            if ($userName != $lastUserName) {
                                                echo "<optgroup label='{$account['UserName']}'>";
                                            }
                                            echo "
										<option value='{$account['ID']}'>{$account['Login']}</option>";
                                            $lastUserName = $userName;
                                        }
                                    }
                                    echo "
								</select>
								</div>";
                                }
    ?>
						</td>
					</tr>
				</table>
				<div class="buttons">
					<?php echo $Interface->DrawButton("OK", "onclick='selectAccount(); return false;'", 120); ?>
					<?php echo $Interface->DrawButton("Cancel", "onclick='hideSelect();cancelPopup(); return false;'", 120, null, 'submit', 'btn-silver'); ?>
				</div>
			</div>
<?php $Interface->DrawEndBox(); ?>
</form>

<script>

var provider = null;
var form = document.getElementById('redeemForm');
function redeem(){
	var result = '';
	for( i=0; i < form.length; i++ )
	{
		var element=form.elements[i];
		if( element.name == 'provider' )
		{
			if( element.type.toLowerCase() == "radio" )
			{
				if( element.checked )
					result = element;
			}
			else
				result = element;
		}
	}
	provider = result.value;

	if(typeof(provider) == 'undefined' || provider == ''){
		alert('Select loyalty program');
		return;
	}
	if(result.getAttribute('data-accounts') == '1')
		form.submit();
	else{
		var select = document.getElementById('provider_' + result.getAttribute('data-provider-id'));
		if(select){
			$(select).closest("div[class='styled-select']").css('display', '');
			//select.style.display = '';
			select.setAttribute('name', 'accountID');
			showPopupWindow(document.getElementById('redeemedAccountPopup'), true);
		}
		else{
			alert("You don't have available accounts");
		}
	}
}

function selectAccount(){
	var select = document.getElementById('provider_' + provider);
	var accountID = select.options[select.selectedIndex].value;
	if(accountID !== ''){
		form.submit();
	}else{
		alert('Select account');
	}
}

function hideSelect(){
	var select = document.getElementById('provider_' + provider);
	$(select).closest("div[class='styled-select']").css('display', 'none');
	//select.style.display = 'none';
	select.removeAttribute('name');
}

</script>
<?php
}

require "$sPath/design/footer.php";

?>
