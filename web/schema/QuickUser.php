<?php
require_once(__DIR__ . "/../lib/schema/BaseQuickUser.php");

class TQuickUserSchema extends TBaseQuickUserSchema
{
    public function GetFormFields()
    {
        $arFields = parent::GetFormFields();
        unset($arFields["Company"]);
        unset($arFields["EmailNewPlans"]);
        unset($arFields["EmailPlansChanges"]);
        unset($arFields["EmailRewardsHeader"]);
        unset($arFields["EmailRewards"]);
        unset($arFields["EmailInviteeReg"]);
        unset($arFields["EmailTCSubscribe"]);
        unset($arFields["EmailProductUpdates"]);
        unset($arFields["EmailOffers"]);
        unset($arFields["EmailFamilyMemberAlert"]);
        unset($arFields["CheckinReminder"]);
        unset($arFields["AutoGatherPlans"]);
        unset($arFields["RegionalHeader"]);
        unset($arFields["HomeAirport"]);
        unset($arFields["TravelHeader"]);
        unset($arFields["Picture"]);
        unset($arFields["Picture"]);
        unset($arFields["SavePassword"]);
        unset($arFields["DisableBrowserExtension"]);
        unset($arFields["2FactorAuth"]);
        if (SITE_MODE == SITE_MODE_BUSINESS) {
            $arFields['Company'] = array(
                "Type" => "string",
                "Size" => 80,
                "Caption" => "Business<br />Name",
                "InputAttributes" => "style='width: 220px;'",
                "Database" => true,
                "Note" => "4-30 characters. English letters only.",
                "RegExp" => "/^[a-z_0-9A-Z\-\s\.\,]+$/i",
                "RegExpErrorMessage" => "Invalid Business Name.",
                "MinSize" => 2,
                "Cols" => 20,
                "filterWidth" => 50,
                "Required" => true
            );
            $picManager = new TPictureFieldManager();
            $picManager->Dir = "/images/uploaded/user";
            $picManager->thumbHeight = 64;
            $picManager->thumbWidth = 64;
            $picManager->KeepOriginal = true;
            $picManager->CreateMedium = true;
            $picManager->ShowUploadButton = false;
            $arFields['Picture'] = array(
                "Type" => "string",
                "Manager" => $picManager,
                "Database" => false,
            );
        } else {
            $arFields['Coupon'] = array(
                "Type" => "string",
                "Size" => 80,
                "Caption" => "Coupon Code",
                "InputAttributes" => "style='width: 220px;'",
                "Database" => false,
                "Value" => ArrayVal($_SESSION, 'RegCouponCode')
            );
        }
        if (isset($_SESSION['KnownUserInfo'])) {
            foreach ($_SESSION['KnownUserInfo'] as $key => $value) {
                if (isset($arFields[$key])) {
                    $arFields[$key]["Value"] = $value;
                }
            }
        }
        return $arFields;
    }

    /**
     * tune editor form
     * @param  $objForm TForm
     * @return void
     */
    public function TuneForm(&$objForm)
    {
        global $Interface;
        parent::TuneForm($objForm);
        if (isset($_GET['BackTo'])) {
            $_SESSION["AuthorizeSuccessURL"] = urlPathAndQuery($_GET['BackTo']);
        }
        if (isset($_SESSION["AuthorizeSuccessURL"])) {
            $objForm->SuccessURL = $_SESSION["AuthorizeSuccessURL"];
        } else {
            $objForm->SuccessURL = "/account/list.php";
        }
        if (ConfigValue(CONFIG_SITE_STATE) == SITE_STATE_PRODUCTION) {
            $objForm->Action = "https://".$Interface->getHTTPSHost().$_SERVER['REQUEST_URI'];
            if (SITE_MODE != SITE_MODE_BUSINESS) {
                $objForm->SuccessURL = "https://".$Interface->getHTTPSHost()."/account/list.php";
            } else {
                $objForm->SuccessURL = "https://".$Interface->getHTTPSHost()."/account/overview.php";
            }
        }
        foreach ($objForm->Fields as $key => $field) {
            if (isset($field["InputAttributes"])) {
                $objForm->Fields[$key]["InputAttributes"] = str_ireplace("300px", "220px", $objForm->Fields[$key]["InputAttributes"]);
            }
            unset($objForm->Fields[$key]["Note"]);
        }
        $objForm->Fields["PassConfirm"]["Caption"] = "Confirm<br>password";
        if ($Interface->SmallScreen) {
            $objForm->SQLParams["Skin"] = "'compact'";
        }
        #		$objForm->Title = "Register";
    }
}
