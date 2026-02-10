<?php

use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\ProviderPhoneResolver;

class TAccountInfo
{
    public $NamedValues;
    public $CodedValues;
    public $Rows;

    public $Number;
    public $NumberField;
    public $NumberCaption;

    public $Status;
    public $StatusField;
    public $StatusCaption;

    public $CustomerSupportPhone;

    public $Fields;

    // allProps - combined all properties, for optimiztions
    public function __construct($arFields, $subAccountID, &$allProps = null)
    {
        global $arExtPropertyStructure;
        $this->NamedValues = [];
        $this->CodedValues = [];
        $this->Rows = [];
        $this->Fields = $arFields;

        if ($arFields["ProviderID"] != "") {
            if (isset($allProps)) {
                if (isset($allProps[$arFields['ID']][$subAccountID])) {
                    $rows = $allProps[$arFields['ID']][$subAccountID];
                    usort($rows, [$this, "sortRows"]);
                } else {
                    $rows = [];
                }
            } else {
                // properties from subaccount will overwrite main account properties
                $rows = [];

                foreach (new TQuery("select pp.Name, pp.Code, ap.Val, pp.Kind, pp.Visible, pp.SortIndex, pp.ProviderID from AccountProperty ap, ProviderProperty pp where ap.ProviderPropertyID = pp.ProviderPropertyID
				and ap.AccountID = {$arFields["ID"]} and (ap.SubAccountID " . (isset($subAccountID) ? " = $subAccountID or ap.SubAccountID is null" : " is null") . ")
				order by IsNull(ap.SubAccountID) desc") as $row) {
                    $rows[$row['Code']] = $row;
                }
                usort($rows, [$this, "sortRows"]);
            }

            foreach ($rows as $fields) {
                $this->NamedValues[$fields["Name"]] = $fields["Val"];
                $this->CodedValues[$fields["Code"]] = $fields["Val"];
                $this->Rows[$fields["Code"]] = $fields;

                if ($fields['Kind'] == PROPERTY_KIND_NUMBER) {
                    $this->NumberField = $fields['Code'];
                    $this->NumberCaption = $fields['Name'];
                    $this->Number = $fields['Val'];
                    unset($this->Rows[$fields["Code"]]);        // remove Account Number from Additional Information
                }

                if ($fields['Kind'] == PROPERTY_KIND_STATUS) {
                    $this->StatusField = $fields['Code'];
                    $this->StatusCaption = $fields['Name'];
                    $this->Status = $fields['Val'];
                    unset($this->Rows[$fields["Code"]]);       // remove Status from Additional Information

                    // lookup phone for this elite level
                    $this->CustomerSupportPhone = self::getSupportPhone($arFields, $fields['Val'], []);

                    if (isset($this->CustomerSupportPhone)) {
                        $this->addProperty('Customer Support Phone', 'CustomerSupportPhone', $this->CustomerSupportPhone);
                    }
                }
            }

            // refs #5597
            if (is_null($this->Status)) {
                $this->CustomerSupportPhone = self::getSupportPhone($arFields, null, []);

                if (isset($this->CustomerSupportPhone)) {
                    $this->addProperty('Customer Support Phone', 'CustomerSupportPhone', $this->CustomerSupportPhone);
                }
            }
        }
    }

    public function addProperty($name, $code, $value, $kind = PROPERTY_KIND_OTHER, $visible = 1)
    {
        $_row = [
            'Name' => $name,
            'Code' => $code,
            'Val' => $value,
            'Kind' => $kind,
            'Visible' => $visible,
        ];
        $this->NamedValues[$_row['Name']] = $_row['Val'];
        $this->CodedValues[$_row['Code']] = $_row['Val'];
        $this->Rows[$_row['Code']] = $_row;
    }

    public static function getSupportPhone($fields, $level, $regions)
    {
        $countryName = null;

        if (!isset($_SESSION['UserCountry'])) {
            if (isset($_SESSION['UserFields']) && isset($_SESSION['UserFields']['CountryID'])) {
                $country = getRepository(\AwardWallet\MainBundle\Entity\Country::class)->find($_SESSION['UserFields']['CountryID']);

                if ($country) {
                    $_SESSION['UserCountry'] = $countryName = $country->getName();
                }
            }

            if (!isset($countryName)) {
                $country = getSymfonyContainer()->get(GeoLocation::class)->getCountryByIp($_SERVER['REMOTE_ADDR']);

                if ($country) {
                    $_SESSION['UserCountry'] = $countryName = $country->getName();
                }
            }
        } else {
            $countryName = $_SESSION['UserCountry'];
        }
        /** @var \Generator $phones */
        $phones = getSymfonyContainer()->get(ProviderPhoneResolver::class)->getUsefulPhones([
            [
                'account' => $fields['ID'],
                'provider' => $fields['ProviderID'],
                'status' => $level,
                'country' => $countryName,
            ],
        ], $regions);
        $phone = $phones[0] ?? null;

        if (!empty($phone) && is_array($phone)) {
            return $phone['Phone'];
        }

        if (count($regions) > 0) {
            return self::getSupportPhone($fields, $level, []);
        }

        return null;
    }

    public function getVisibleProperties($keyField = 'Code', $onlyValues = false)
    {
        $visibleProps = [];

        foreach ($this->Rows as $fields) {
            if ($fields['Visible'] == 1) {
                if ($onlyValues) {
                    $visibleProps[$fields[$keyField]] = $fields['Val'];
                } else {
                    $visibleProps[$fields[$keyField]] = $fields;
                }
            }
        }

        return $visibleProps;
    }

    public function NumberOrLogin()
    {
        if (isset($this->Number)) {
            return $this->Number;
        } else {
            return htmlspecialchars($this->Fields['Login']);
        }
    }

    public static function getSubaccountsInfo($arFields, $onlyActiveDeals = true)
    {
        $subAccounts = SQLToArray("select * from SubAccount
			where AccountID = {$arFields['ID']} order by DisplayName", "SubAccountID", "DisplayName", true);
        // Subaccounts - deals?
        $isCoupon = (isset($subAccounts[0]['Kind']) && $subAccounts[0]['Kind'] == 'C');
        // Subaccounts - Subaccount?
        $isSubaccount = (isset($subAccounts[0]['Kind']) && $subAccounts[0]['Kind'] == 'S');

        foreach ($subAccounts as $key => $singleSub) {
            $subAccounts[$key]['ID'] = $subAccounts[$key]['AccountID'];
            $subAccounts[$key]['SubAccountInfo'] = new TAccountInfo($arFields, $singleSub['SubAccountID']);

            if ($isCoupon) {
                $cert = $subAccounts[$key]['SubAccountInfo']->CodedValues['Certificates'] = @unserialize($subAccounts[$key]['SubAccountInfo']->CodedValues['Certificates']);

                if (empty($subAccounts[$key]['ExpirationDate'])) {
                    $subAccounts[$key]['sExpirationDate'] = 9999999999;
                } else {
                    $subAccounts[$key]['sExpirationDate'] = strtotime($subAccounts[$key]['ExpirationDate']);
                }

                if ($subAccounts[$key]['ExpirationAutoSet'] == EXPIRATION_UNKNOWN) {
                    $subAccounts[$key]['ExpirationAutoSet'] = EXPIRATION_AUTO;
                }

                if ($cert !== false) {
                    $subAccounts[$key]['SubAccountInfo']->CodedValues['Quantity'] = 0;

                    foreach ($cert as $idCoupon => $singleCoupon) {
                        if ($singleCoupon['Used'] == false && time() < $singleCoupon['ExpiresAt']) {
                            $subAccounts[$key]['SubAccountInfo']->CodedValues['Quantity']++;
                        }
                    }
                } else {
                    unset($subAccounts[$key]);

                    continue;
                }
                unset($cert);

                if ($subAccounts[$key]['SubAccountInfo']->CodedValues['Quantity'] == 0 && $onlyActiveDeals) {
                    unset($subAccounts[$key]);

                    continue;
                }
            }// if ($isCoupon)

            if ($isSubaccount && $onlyActiveDeals) {
                if (!empty($subAccounts[$key]['ExpirationDate'])) {
                    $subAccountExpirationDate = strtotime($subAccounts[$key]['ExpirationDate']);

                    if (time() > $subAccountExpirationDate) {
                        unset($subAccounts[$key]);

                        continue;
                    }
                }// if (!empty($subAccounts[$key]['ExpirationDate']))
            }// if ($isSubaccount && $onlyActiveDeals)
        }

        // Sort deals
        if ($isCoupon && sizeof($subAccounts)) {
            usort($subAccounts, function ($a, $b) use ($arFields) {
                if (!isset($a['sExpirationDate']) || !isset($b['sExpirationDate'])) {
                    if ($arFields['SavePassword'] == SAVE_PASSWORD_DATABASE) {
                        DieTrace('Daily Deals error. Coupon expiration date not found.', false);
                    }

                    return 0;
                }

                if ($a['SubAccountInfo']->CodedValues['Quantity'] == $b['SubAccountInfo']->CodedValues['Quantity']) {
                    if ($a['sExpirationDate'] == $b['sExpirationDate']) {
                        return 0;
                    }

                    return ($a['sExpirationDate'] < $b['sExpirationDate']) ? 1 : -1;
                }

                return ($a['SubAccountInfo']->CodedValues['Quantity'] < $b['SubAccountInfo']->CodedValues['Quantity']) ? 1 : -1;
            }
            );
        }// if ($isCoupon && sizeof($subAccounts))

        // Sort subAccounts
        if ($isSubaccount && sizeof($subAccounts)) {
            usort($subAccounts, function ($a, $b) {
                if (!isset($a['ExpirationDate']) || !isset($b['ExpirationDate'])) {
                    return 1;
                }

                return ($a['ExpirationDate'] < $b['ExpirationDate']) ? -1 : 1;
            }
            );
        }// if ($isSubaccount && sizeof($subAccounts))

        return $subAccounts;
    }

    protected function sortRows($a, $b)
    {
        if ($a['SortIndex'] > $b['SortIndex']) {
            return 1;
        } elseif ($a['SortIndex'] < $b['SortIndex']) {
            return -1;
        } else {
            return 0;
        }
    }
}
