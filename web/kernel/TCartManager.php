<?php

require_once __DIR__ . "/../lib/classes/TBaseCartManager.php";

require_once __DIR__ . "/TOneOptionForm.php";

class TCartManager extends TBaseCartManager
{
    public const RECURRING_PAYMENT_TITLE = 'Recurring Payment for AwardWallet Plus';

    public $isRecurringPayment = false;
    public $recurringAmount;
    public $recurringItem;
    public $existsOneCardCredits = false;
    public $fromIPN = false;

    public function __construct()
    {
        global $objCart;
        parent::__construct();
        $this->CompleteScript = '/cart/complete.php';

        if (isset($objCart)) {
            $objCart->CalcTotals();
            $this->ShowCoupons = !($objCart->TypeExists(CART_ITEM_TOP10) || ($objCart->TypeExists(CART_ITEM_AWB)
                    || $objCart->TypeExists(CART_ITEM_AWB_PLUS) || $objCart->TypeExists(CART_ITEM_BOOKING)));
            $this->ShowShippingAddress = $objCart->TypeExists(CART_ITEM_ONE_CARD_SHIPPING);
        }
        $this->initCreditCardPayment();
        getSymfonyContainer()->get("aw.manager.logo")->setBookingRequest($objCart->getBookingRequest());
    }

    public function initCreditCardPayment()
    {
        global $objCart, $arFormValues, $arAddress;
        $this->allowTotalZero = true;

        if (isset($objCart) && $objCart->TypeExists(CART_ITEM_BOOKING)) {
            $this->allowTotalZero = false;
        }
        $isAWBusiness = SITE_MODE == SITE_MODE_BUSINESS;
    }

    public function GetMailItemName($fields)
    {
        if ($fields['TypeID'] == CART_ITEM_ONE_CARD) {
            $this->existsOneCardCredits = true;

            return 'AwardWallet OneCard Credits';
        }

        return parent::GetMailItemName($fields);
    }

    public function SelectPaymentTypeForm()
    {
        global $arPaymentType, $objCart;
        $arPaymentType[PAYMENTTYPE_CREDITCARD] = "<img src='/images/cards.png' style='width: 355px; height: 63px;' alt='Visa, Mastercard, Amex, Discover'>";
        $arPaymentType[PAYMENTTYPE_PAYPAL] = "<table border='0' cellspacing='0'><tr><td><img src='/images/paypal.png' style='width: 84px; height: 63px; float: left; margin-right: 10px;' alt='PayPal'></td><td><div style='color: #666666; font-size: 12px;'>Save time. Check out securely. <br>Pay without sharing your financial information.</div></td></tr></table>";
        $objCart->CalcTotals();

        if ($objCart->TypeExists(CART_ITEM_AWPLUS_RECURRING)) {
            unset($arPaymentType[PAYMENTTYPE_BITCOIN]);
        }

        return new TOneOptionForm([
            "PaymentType" => [
                "Type" => "integer",
                "InputType" => "radio",
                "Options" => $arPaymentType,
                "Required" => true,
            ],
        ]);
    }

    public function DrawPayFree()
    {
        global $objCart;

        if (isset($objCart->Coupon) && ($objCart->Coupon == "Invite bonus") && preg_match("/^Invite\-{$_SESSION['UserID']}\-/ims", Lookup("Coupon", "CouponID", "Code", $objCart->CouponID, true))) {
            echo "<div style=\"font-size: 20px; text-align: left; color: #0b70b7; line-height: 22px;\">
			Thank you for inviting people to AwardWallet.com, you made the right choice when
			recommended our service! In return we promise to do everything possible to impress
			you and the people you’ve invited with the best service possible.
			We also offer you our six month AwardWallet Plus service for free for each
			<span style=\"color: #93c7ec; font-size: 24px; font-weight: bold;\">five</span>
			new members that you bring to our website.</div>";
        } else {
            parent::DrawPayFree();
        }
    }

    public function CheckShippingPage()
    {
        global $Interface;

        if (!isset($_SESSION['OrderedOneCards'])) {
            $Interface->DiePage("Your session has expired. Please <a href='/onecard/'>order OneCard</a> again.");
        } /* checked */
    }

    public function MarkAsPayed()
    {
        global $Connection, $objCart, $objForm;
        $shipping = $objCart->TypeExists(CART_ITEM_ONE_CARD_SHIPPING);

        if ($shipping) {
            if (!$this->ShowShippingAddress) {
                DieTrace("invalid request, trying to pay free order with shipping");
            }
            $this->CheckShippingPage();
        }
        parent::MarkAsPayed();

        if ($shipping) {
            $text = "UserID: {$_SESSION['UserID']}, {$_SESSION['FirstName']} {$_SESSION['LastName']}\nCards for:\n";

            foreach ($_SESSION['OrderedOneCards'] as $row) {
                $row['CartID'] = $objCart->ID;
                $Connection->Execute(InsertSQL("OneCard", $row));
                $text .= "{$row['FullName']}: {$row['TotalMiles']} miles\n";
            }
            unset($_SESSION['OrderedOneCards']);
        }
        $q = new TQuery("select * from CartItem where CartID = {$objCart->ID}
		and TypeID  in(" . CART_ITEM_AWPLUS . ", " . CART_ITEM_AWPLUS_20 . ", " . CART_ITEM_AWPLUS_1 . ", " . CART_ITEM_AWPLUS_TRIAL . ")");

        if (!$q->EOF) {
            DieTrace("Cart should not contain plus items");
        }

        $q = new TQuery("
			select * from CartItem
			where
				CartID = {$objCart->ID}
				and TypeID = " . CART_ITEM_BOOKING . "
				and UserData is not null
		");

        if (!$q->EOF) {
            DieTrace("Cart should not contain booking items");
        }
    }

    public function ShippingComplete()
    {
        Redirect("/onecard/success.php");
    }

    // mark one cart item as paid. input: CartItem table row
    // do not suggest that that order belongs to $_SESSION['UserID'],
    // use $objCart to read information
    public function MarkItemPaid($arFields)
    {
        global $Connection, $objCart;
        parent::MarkItemPaid($arFields);
        $qCart = new TQuery("select * from Cart where CartID = " . $objCart->ID);
        $nUserID = $qCart->Fields["UserID"];

        if ($_SESSION['UserID'] != $nUserID) {
            DieTrace("Other user upgrade");
        }

        // convert contribution to upgrade?
        if (($_SESSION['AccountLevel'] == ACCOUNT_LEVEL_AWPLUS) && ($arFields['TypeID'] == CART_ITEM_TOP10)) {
            GetAccountExpiration($_SESSION['UserID'], $dDate, $nLastPrice);

            if ((time() >= strtotime("-1 month", $dDate)) && ($arFields["Price"] >= 5)) {
                $q = new TQuery("select * from CartItem where CartID = {$objCart->ID} and TypeID = " . CART_ITEM_AWPLUS);

                if ($q->EOF) {
                    $Connection->Execute("update CartItem set TypeID = " . CART_ITEM_AWPLUS . " where CartID = {$objCart->ID} and TypeID = " . CART_ITEM_TOP10);
                }
            }
        }
    }

    public function DrawCreditCardBillingInfo($objForm)
    {
        global $Interface, $objCart;
        echo "<div style=\"margin-right: 20px;\">";
        $this->DrawWarnings();
        echo "<div style=\"width: 600px;\">";
        $Interface->drawSectionDivider("Order Details");
        $this->DrawContents();
        echo "</div><br>";

        if (isset($objForm->Fields["Notes"])) {
            $this->DrawNotes($objForm);
        }

        if ($this->ShowCoupons) {
            $this->DrawCouponForm($objForm);
        }

        if ($objCart->Anonymous && !isset($_SESSION['UserID'])) {
            $this->DrawRegistrationForm($objForm);
        }

        if ($this->ShowShippingAddress) {
            $this->DrawAddressForm("Shipping", $objForm);
        }
        $this->DrawAddressForm("Billing", $objForm);
        echo "<br><div style=\"width: 600px;\">";
        $Interface->drawSectionDivider("Credit Card Info");
        $this->DrawCreditCardForm($objForm);
        echo "<br></div>";
        $this->DrawAddressScripts();
        echo "</div>";
    }

    public function DrawShippingInfo($objForm)
    {
        global $Interface, $objCart;
        echo "<div style=\"margin-right: 20px;\">";
        $this->DrawWarnings();
        echo "<div style=\"width: 600px;\">";
        $Interface->drawSectionDivider("Order Details");
        $this->DrawContents(false, "Quantity");
        echo "</div><br>";

        if (isset($objForm->Fields["Notes"])) {
            $this->DrawNotes($objForm);
        }

        if ($this->ShowCoupons) {
            $this->DrawCouponForm($objForm);
        }

        if ($objCart->Anonymous && !isset($_SESSION['UserID'])) {
            $this->DrawRegistrationForm($objForm);
        }

        if ($this->ShowShippingAddress) {
            $this->DrawAddressForm("Shipping", $objForm);
        }
        echo "<br></div>";
        $this->DrawAddressScripts();
    }

    public function DrawCreditCardPreview($objForm, $arBillingAddress, $arShippingAddress)
    {
        global $objCart;
        $objCart->CalcTotals();
        //		if(!isset($_POST['AdSelected']) && $objCart->Exists(CART_ITEM_AWPLUS, $_SESSION['UserID'])){
        //			$_SESSION['CardPost'] = $_POST;
        //			$_SESSION['PayMethod'] = 'CreditCard';
        //		}
        echo '<div style="width: 600px;">';
        parent::DrawCreditCardPreview($objForm, $arBillingAddress, $arShippingAddress);
        echo '</div><br/>';
    }

    public function DrawShippingPreview($objForm, $arShippingAddress)
    {
        global $Interface; ?>
<div style="width: 600px; padding-bottom: 20px;">
<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr>
    <td>
<?php
echo "<div style=\"margin: 0 auto; width: 600px;\">";
        $Interface->drawSectionDivider("Your order");
        echo "</div>";

        foreach ($objForm->Fields as $sFieldName => $arField) {
            echo "<input type=hidden name={$sFieldName} value=\"" . htmlspecialchars(ArrayVal($arField, "Value")) . "\">\r\n";
        }
        $this->DrawContents(false, "Quantity");

        if (isset($objForm->Fields["Notes"])) {
            $this->DrawNotesPreview($objForm);
        }
        $this->DrawAddressInfo("Shipping", $arShippingAddress); ?>
    </td>
</tr>
</table>
</div>
<?php
    }

    public function DrawContents($showTotal = true, $secondCol = "Price")
    {
        global $objCart, $lastRowKind;

        if ($objCart->TypeExists(CART_ITEM_BOOKING)) {
            $this->DrawContentsBooking();

            return;
        }

        $lastRowKind = "First"; ?>
		<table cellspacing='0' cellpadding='0' border='0' class='roundedTable borderLess' style="width: 100%; margin-top: 25px;">
            <tr class='grayHead noWrap after<?php echo $lastRowKind; ?>'>
				<td style="width: 80%">
					<div class="icon"><div class="inner"></div></div>
					<div class="caption">Item</div>
				</td>
				<td style="width: 20%">
					<div class="icon rightIcon"><div class="inner"></div></div>
                    <div class="caption"><?php echo $secondCol; ?></div>
				</td>
			</tr>
			<?php $lastRowKind = "Group"; ?>
			<?php
            $q = new TQuery("select * from CartItem where CartID = {$objCart->ID} order by TypeID, CartItemID");

        while (!$q->EOF) {
            $classes = "after" . $lastRowKind . " whiteBg";
            $lastRowKind = "White";

            if ($secondCol == "Price") {
                $value = "$" . number_format_localized($q->Fields["Price"], 2);
            } else {
                $value = $q->Fields["Cnt"];
            }
            $value = str_replace('$-', '-$', $value); ?>
            <tr class="<?php echo $classes; ?>">
					<td class="rightDots<?php if ($showTotal) {
					    echo " borderBottom";
					} ?>">
						<div class="caption pad leftBorder"><?php
					        echo $q->Fields["Name"];
            echo " (TypeID: {$q->Fields['TypeID']})";

            if ($q->Fields["Description"] != "") {
                echo "<br>" . $q->Fields["Description"];
            } ?></div>
					</td>
					<td class="borderRight<?php if ($showTotal) {
					    echo " borderBottom";
					} ?> pad"><?php echo $value; ?></td>
				</tr>
				<?php
            $q->Next();
        }

        if (isset($objCart->Coupon)) {
            $classes = "after" . $lastRowKind . " whiteBg total";
            $lastRowKind = "White"; ?>
            <tr class="<?php echo $classes; ?>">
					<td class="rightDots">
						<div class="caption pad leftBorder">Subtotal</div>
					</td>
                <td class="borderRight pad"><?php echo "$" . number_format_localized($objCart->Price, 2); ?></td>
				</tr>
				<tr class="afterWhite whiteBg total">
					<td class="rightDots">
                        <div class="caption pad leftBorder">Coupon <span
                                style="color: Black;">"<?php echo $objCart->Coupon; ?>"</span></div>
					</td>
                    <td class="pad borderRight"><?php echo "-$" . number_format_localized($objCart->DiscountAmount, 2); ?></td>
				</tr>
				<?php
        }

        if ($showTotal) {
            $caption = "Total"; ?>
			<tr class="afterWhite whiteBg total">
				<td class="rightDots">
                    <div class="caption pad leftBorder"><?php echo $caption; ?>:</div>
				</td>
                <td class="borderRight pad"><?php echo "$" . number_format_localized($objCart->Total, 2); ?></td>
			</tr>
            <?php
        } ?>
			<tr class='lastRow'>
				<td class="lastRow rightDots"><div class="icon"></div></td><td class="borderRight"><div class="rightIcon"></div></td>
			</tr>
		</table>
		<?php
    }

    public function DrawContentsBooking()
    {
        global $objCart, $lastRowKind;
        $lastRowKind = "First"; ?>
		<table cellspacing='0' cellpadding='0' border='0' class='roundedTable borderLess' style="width: 100%; margin-top: 25px;">
            <tr class='grayHead noWrap after<?php echo $lastRowKind; ?>'>
				<td style="width: 60%">
					<div class="icon"><div class="inner"></div></div>
					<div class="caption">Item</div>
				</td>
				<td style="width: 20%">
					<div class="icon rightIcon"><div class="inner"></div></div>
					<div class="caption">Price</div>
				</td>
			</tr>
			<?php $lastRowKind = "Group"; ?>
			<?php
            $q = new TQuery("select * from CartItem where CartID = {$objCart->ID} order by CartItemID");

        while (!$q->EOF) {
            $classes = "after" . $lastRowKind . " whiteBg";
            $lastRowKind = "White";
            $value = "$" . number_format_localized($q->Fields["Price"] * $q->Fields["Cnt"], 2);
            $value = str_replace('$-', '-$', $value); ?>
            <tr class="<?php echo $classes; ?>">
					<td class="borderRight borderBottom">
						<div class="caption pad leftBorder"><?php
                            echo $q->Fields["Name"];

            if ($q->Fields["Description"] != "") {
                echo "<br>" . $q->Fields["Description"];
            } ?></div>
					</td>
                <td class="borderRight borderBottom pad"><?php echo $value; ?></td>
				</tr>
				<?php
                $q->Next();
        } ?>
			<tr class="afterWhite whiteBg total">
				<td class="borderRight">
					<div class="caption pad leftBorder">Total:</div>
				</td>
                <td class="borderRight pad"><?php echo "$" . number_format_localized($objCart->Total, 2); ?></td>
			</tr>
			<tr class='lastRow'>
				<td class="lastRow rightDots">
					<div class="icon"></div>
				</td>
				<td class="borderRight">
					<div class="rightIcon"></div>
				</td>
			</tr>
		</table>
		<?php
    }

    public function DrawPayPalForm($objForm)
    {
        global $objCart, $Interface;
        //		if(!isset($_GET['AdSelected']) && $objCart->Exists(CART_ITEM_AWPLUS, $_SESSION['UserID']))
        echo "<div style='max-width: 600px;'>"; ?>
<form method=post name=editor_form onsubmit="submitonce(this)">
<input type=hidden name=submitButton>
<input type=hidden name=CalcNewShipping value=''>
<input type=hidden name=DisableFormScriptChecks value=0>
    <input type="hidden" name="FormToken" value="<?php echo GetFormToken(); ?>">
<table cellspacing="0" cellpadding="0" border="0" align="center" width="100%">
<tr>
	<td align="center">
<?php
if (isset($objForm->Error)) {
    $Interface->DrawMessage($objForm->Error, "error");
} ?>
	</td>
</tr>
<tr>
	<td><?$this->DrawWarnings(); ?></td>
</tr>
<tr>
    <td height="25"><?php $Interface->drawSectionDivider("Your order details:"); ?><br></td>
</tr>
<tr>
	<td><?$this->DrawContents(); ?></td>
</tr>
<?php if ($this->ShowCoupons) { ?>
<tr>
	<td>
		<table cellspacing="0" cellpadding="0" border="0" style="margin-top: 10px;" class="formTable">
<?php
echo $objForm->FormatRowHTML("CouponCode", $objForm->Fields["CouponCode"], "<table cellspacing='0'><tr><td>" . $objForm->InputHTML("CouponCode")
    . "</td><td>&nbsp;</td><td><input type='hidden' name='addCouponButton'/>"
    . $Interface->DrawButton("Enter", "", 0, "name=addCouponButtonTrigger value=Enter onclick=\"if( CheckForm( this.form ) ) { this.form.addCouponButton.value = '1'; return true; } else return false;\"") . "</td></tr></table>");
    ?>
</table>
	</td>
</tr>
<?php }

if (isset($objForm->Fields["Notes"])) {
    echo "<tr><td>";
    $this->DrawNotes($objForm);
    echo "</td></tr>";
}

        if ($objCart->Anonymous && !isset($_SESSION['UserID'])) {
            echo "<tr><td>";
            $this->DrawRegistrationForm($objForm);
            echo "</td></tr>";
        } ?>
<tr>
	<td>
<br>
<?php
if ($this->ShowShippingAddress) {
    $this->DrawAddressForm("Shipping", $objForm);
} ?>
<?php $this->DrawAddressScripts(); ?>
<div align="center">
    <?// $Interface->DrawButton2("Proceed to PayPal.com for Authentication", "name=submitButtonTrigger onclick=\"if( CheckForm( document.forms['editor_form'] ) ) { this.form.submitButton.value='submit'; return true; } else return false;\"")
    ?>
    <?php echo $objForm->ButtonsHTML(); ?></div>
	</td>
</tr>
</table>
</form>
        <?php echo $objForm->CheckScripts(); ?>
<?php
        echo "</div>";
    }

    public function DrawSelectPaymentType(&$objForm)
    {
        global $Interface;
        echo "<div style=\"width: 600px;\">";
        $Interface->drawSectionDivider("Select Payment Type");
        echo $objForm->HTML();
        echo "</div><br>";
    }

    public function DrawHeader()
    {
        global $objCart;

        require __DIR__ . "/../design/topMenu/main.php";
        parent::DrawHeader();

        if (!empty($objCart->getBookingRequest())) {
            echo '<style>#topButtons {display: none;} #footer {display: none;} #leftBar {display: none;} div#content {padding-left:10px;}</style>';
        }
    }

    public function DrawFooter()
    {
        echo "<br>";
        parent::DrawFooter();
    }

    public function DrawPaypalConfirmation(&$details)
    {
        global $objCart, $Interface;
        $Interface->DrawBeginBox('id="acceptPopup" style="height: 400px; width: 600px; position: absolute; z-index: 60; display: none;"', "Confirm payment", false); ?>
		<form method=post onsubmit="submitonce(this)">
            <input type="hidden" name="FormToken" value="<?php echo GetFormToken(); ?>">
		<div  style="padding: 20px; text-align: left;">
			<div>
                Dear <?php echo $details->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->FirstName; ?> <?php echo $details->GetExpressCheckoutDetailsResponseDetails->PayerInfo->PayerName->LastName; ?>
                , do you want to pay <b>$<?php echo $objCart->Total; ?></b> for <b><?php echo $objCart->Names; ?></b>?
			</div>
			<div style="margin-top: 40px;">
				<?php echo $Interface->DrawButton("Pay", "", 0); ?>
				<?php echo $Interface->DrawButton("Cancel", "", 0, "onclick=\"window.location.href='/'; return false;\""); ?>
			</div>
		</div>
		</form>
		<?php
        $Interface->DrawEndBox();
        $Interface->FooterScripts[] = "showPopupWindow(document.getElementById('acceptPopup'), true);";
    }

    public function DrawCouponForm($objForm)
    {
        global $Interface; ?>
<div style="width: 600px;"><table cellspacing="0" cellpadding="0" border="0" style="margin-top: 10px;" class="formTable">
<?php
echo $objForm->FormatRowHTML("CouponCode", $objForm->Fields["CouponCode"], "<table cellspacing='0'><tr><td>" . $objForm->InputHTML("CouponCode")
    . "</td><td>&nbsp;</td><td><input type='hidden' name='addCouponButton'/>"
    . $Interface->DrawButton("Enter", "", 0, "name=addCouponButtonTrigger value=Enter onclick=\"this.form.DisableFormScriptChecks.value='1'; this.form.NewFormPage.value = 'BillingInfo'; if( CheckForm( this.form ) ) { this.form.addCouponButton.value = '1'; return true; } else return false;\"") . "</td></tr></table>"); ?>
</table></div>
<br>
<?php
    }

    public function DrawFreePage()
    {
        global $Interface;
        echo "<div style=\"width: 600px;\">";
        echo '<form method=post>';
        echo '<input type="hidden" name="FormToken" value="' . GetFormToken() . '">';
        $this->DrawWarnings();
        $Interface->drawSectionDivider("Free upgrade confirmation");
        $this->DrawPayFree();
        echo "<br>";
        echo $Interface->DrawButton("Continue", "", 0, "name='pay'");
        echo "</div>";
    }

    public function TuneForm(&$objForm)
    {
        parent::TuneForm($objForm);

        foreach ($objForm->Fields as $fieldName => &$field) {
            if (isset($field["InputAttributes"])) {
                $field["InputAttributes"] = str_ireplace("222px", "210px", $field["InputAttributes"]);
            }
        }
    }

    public function DrawAddressForm($sAddressType, $objForm)
    {
        global $Interface;
        echo "<div style='width: 600px;'>";

        if (isset($_SESSION['UserID'])) {
            $qAddress = new TQuery("select ba.*, s.Name as StateName, s.Code as StateCode, c.Name as CountryName
from {$sAddressType}Address ba, State s, Country c
where ba.StateID = s.StateID and ba.CountryID = c.CountryID
and ba.UserID = {$_SESSION["UserID"]}");
        }

        $sAddressTypeLow = strtolower($sAddressType); ?>
        <?// begin existing addresses
        ?>
<?php if (isset($_SESSION['UserID']) && !$qAddress->EOF) { ?>
        <?php $Interface->drawSectionDivider("<div style='margin-top: 15px;'>Saved $sAddressType Addresses</div>"); ?>
<div style='height: 10px;'></div>
<?php
while (!$qAddress->EOF) {
    if (($qAddress->Position % 2) == 1) {
        echo "<div class='clear'></div>";
    }
    echo "<div style='float: left; width: 50%;'><div style='padding-right: 10px;'>
	<div>
		<input type=radio name={$sAddressType}AddressID value={$qAddress->Fields["{$sAddressType}AddressID"]} onclick=\"AddressChanged( this.form, '{$sAddressType}', radioValue( this.form, '{$sAddressType}AddressID' ) == '0' )\"";

    if ($objForm->Fields["{$sAddressType}AddressID"]["Value"] == $qAddress->Fields["{$sAddressType}AddressID"]) {
        echo " checked";
    }
    echo "> <b>{$qAddress->Fields["AddressName"]}</b>
		[<a href=editAddress.php?ID={$qAddress->Fields["{$sAddressType}AddressID"]}&Type={$sAddressType}>Edit</a>] [<a onclick=\"return window.confirm('Delete {$sAddressTypeLow} address?')\" href=deleteAddress.php?ID={$qAddress->Fields["{$sAddressType}AddressID"]}&Type={$sAddressType}>Delete</a>]
	</div>
	<div style='padding-left: 24px; padding-top: 3px;'>
		<div>{$qAddress->Fields["FirstName"]} {$qAddress->Fields["LastName"]}</div>
		<div>{$qAddress->Fields["Address1"]}</div>";

    if ($qAddress->Fields["Address2"] != "") {
        echo "<div>{$qAddress->Fields["Address2"]}</div>";
    }

    if ($qAddress->Fields["CountryName"] == "United States") {
        echo "<div>{$qAddress->Fields["City"]} {$qAddress->Fields["StateCode"]}, {$qAddress->Fields["Zip"]}</div>";
    } else {
        echo "<div>{$qAddress->Fields["City"]}, {$qAddress->Fields["StateName"]}, {$qAddress->Fields["Zip"]}, {$qAddress->Fields['CountryName']}</div>";
    }
    echo "</div>";
    echo "</div></div>";
    $qAddress->Next();
}
            ?>
<?php } ?>
        <?// end existing addresses
            ?>
        <?// begin new address
            ?>
<?php
        $showRadioButton = true;
        $title = "New $sAddressType Address";

        if (count($objForm->Fields["{$sAddressType}AddressID"]["Options"]) == 1) {
            $showRadioButton = false;
            echo "<input type=hidden name={$sAddressType}AddressID value=0>";
        } else {
            $title = "<input type=radio name={$sAddressType}AddressID value='0' onclick=\"AddressChanged( this.form, '{$sAddressType}', radioValue( this.form, '{$sAddressType}AddressID' ) == '0' )\"" . (($objForm->Fields["{$sAddressType}AddressID"]["Value"] == "0") ? " checked" : "") . "> " . $title;
        }
        $title = "<div style='margin-top: 15px;'>$title</div>";
        $Interface->drawSectionDivider($title); ?>
<div style='height: 10px;'></div>
        <?// begin the actual new address form
        ?>
<table cellspacing='0' cellpadding='0' border='0' class='formTable' width="100%">
	<tr><td colspan=4 align=center><div class="blueNote">Fields marked with (*) are required</div></td></tr>
	<?php
    echo $objForm->InputHTML("{$sAddressType}AddressName", null, true);
        echo $objForm->InputHTML("{$sAddressType}FirstName", null, true);
        echo $objForm->InputHTML("{$sAddressType}LastName", null, true);
        echo $objForm->InputHTML("{$sAddressType}Address1", null, true);
        echo $objForm->InputHTML("{$sAddressType}Address2", null, true);
        echo $objForm->InputHTML("{$sAddressType}City", null, true);
        echo $objForm->InputHTML("{$sAddressType}CountryID", null, true);
        echo $objForm->InputHTML("{$sAddressType}StateID", null, true);
        echo $objForm->InputHTML("{$sAddressType}Zip", null, true); ?>
</table>
        <?// end the actual new address form
        ?>
        <?// end new address
        ?>
<?php
        echo "</div>";
    }

    public function DrawCreditCardForm($objForm)
    {
        ?>
        <table style='margin-top: 10px;' cellspacing='0' cellpadding='0' border='0' class='formTable'
               align="center"<?php echo $this->formParams; ?>>
	<tr><td colspan=4 align=center><div class="blueNote">Fields marked with (*) are required</div></td></tr>
	<?php
    echo $objForm->InputHTML("CreditCardType", null, true);
        echo $objForm->InputHTML("CreditCardNumber", null, true);
        echo $objForm->InputHTML("SecurityCode", null, true);
        $s = '<table border="0" cellpadding="0" cellspacing="0"><tr><td>' . $objForm->InputHTML("ExpirationMonth") . '</td><td>&nbsp;</td>
	<td>' . $objForm->InputHTML("ExpirationYear") . '</td></tr></table>';
        $objForm->Fields["ExpirationYear"]["Caption"] = "Expiration Date";
        echo $objForm->FormatRowHTML("ExpirationYear", $objForm->Fields["ExpirationYear"], $s);
        $objForm->Fields["ExpirationYear"]["Caption"] = "Expiration Year"; ?>
</table>
<br>
        <div class="fieldhint">* <?php echo SITE_NAME; ?> does not store your credit card information, therefore you
            have to specify your credit card each time you submit a new order. We are doing it to make our site more
            secure and to prevent any credit card fraud. We apologize for the inconvenience it causes you.
        </div>

<?php
    }

    public function getDetailsData()
    {
        global $objCart;
        $q = new TQuery("select * from CartItem where CartID = {$objCart->ID} order by TypeID");
        $total = 0;
        $result = $data = [];

        while (!$q->EOF) {
            if ($q->Fields["TypeID"] == CART_ITEM_TYPE_SINGLE) {
                $data['purchase'] = $this->GetMailItemName($q->Fields);
                $data['price'] = '';
                $data['qty'] = '';
                $data['total'] = $q->Fields["Price"] * $q->Fields["Cnt"];
            } else {
                $data['purchase'] = $this->GetMailItemName($q->Fields);
                $data['price'] = $q->Fields["Price"];
                $data['qty'] = $q->Fields["Cnt"];
                $data['total'] = $q->Fields["Price"] * $q->Fields["Cnt"];
            }

            $total += $q->Fields["Price"] * $q->Fields["Cnt"];
            $result[] = $data;
            $q->Next();
        }

        if ($objCart->Coupon != "") {
            $data['purchase'] = "Coupon '{$objCart->Coupon}'";
            $data['price'] = "";
            $data['qty'] = "";
            $data['total'] = $objCart->DiscountAmount;
            $result[] = $data;
            $total -= $objCart->DiscountAmount;
        }

        return [
            'data' => $result,
            'total' => $total,
        ];
    }

    public function SendMailCCPaymentComplete()
    {
        $this->sendMailPaymentComplete();
    }

    public function SendMailPaypalPaymentComplete()
    {
        $this->sendMailPaymentComplete(false);
    }

    public function sendMailPaymentComplete($isCreditCard = true)
    {
        global $objCart;

        $newCartManager = getSymfonyContainer()->get('aw.manager.cart');
        /** @var \AwardWallet\MainBundle\Entity\Cart $cart */
        $cart = getSymfonyContainer()->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)->find($objCart->ID);

        if ($cart->allowSendMailPaymentComplete()) {
            $newCartManager->sendMailPaymentComplete($cart);
        }
    }
}
