<?php

namespace AwardWallet\MainBundle\Globals\AccountList\Mapper;

use AwardWallet\MainBundle\Globals\BarcodeCreator;

class ExportListMapper extends Mapper
{
    protected const DATA_TEMPLATE = [
        'ID' => null,
        'FID' => null,
        'ProviderID' => null,
        'ConnectedAccount' => null,
        'TableName' => null,
        'State' => null,
        'isCustom' => null,
        'Kind' => null,
        'DisplayName' => null,
        'DisplayNameFormated' => null,
        'SavePassword' => null,
        'Balance' => null,
        'BalanceRaw' => null,
        'TotalBalanceChange' => null,
        'USDCash' => null,
        'USDCashRaw' => null,
        'USDCashMileValue' => null,
        'TotalUSDCash' => null,
        'TotalUSDCashRaw' => null,
        'TotalUSDCashChange' => null,
        'MileValue' => null,
        'LastChange' => null,
        'LastChangeDate' => null,
        'LastChangeDateTs' => null,
        'ChangeCount' => null,
        'ChangedOverPeriodPositive' => null,
        'AllianceAlias' => null,
        'AllianceIcon' => null,
        'EliteStatuses' => null,
        'EliteLevelsCount' => null,
        'Elitism' => null,
        'Rank' => null,
        'ExpirationDate' => null,
        'ExpirationDateTs' => null,
        'ExpirationDateTip' => null,
        'ExpirationKnown' => null,
        'ExpirationState' => null,
        'ExpirationStateType' => null,
        'ExpirationMode' => null,
        'ExpirationModeType' => null,
        'ExpirationDetails' => null,
        'ErrorCode' => null,
        'AutoLogin' => null,
        'LoginURL' => null,
        'Goal' => null,
        'GoalProgress' => null,
        'GoalIndicators' => null,
        'UpdateDate' => null,
        'UpdateDateTs' => null,
        'SubAccountsArray' => null,
        'Access' => null,
        'Shares' => null,
        'ProgramMessage' => null,
        'ProviderCode' => null,
        'CheckInBrowser' => null,
        'Login' => null,
        'MobileAutoLogin' => null,
        // Status (from main properties)
        'AccountStatus' => null,
        'StatusIndicators' => null,
        'StateBar' => null,
        'LastDurationWithoutPlans' => null,
        'LastDurationWithPlans' => null,
        'HasCurrentTrips' => null,
        'IsActiveTab' => null,
        'SuccessCheckDateTs' => null,
        'EmailDate' => null,
        'EmailDateTs' => null,
        'CanSavePassword' => null,
        'CanReceiveEmail' => null,
        'HistoryName' => null,
        'CanCheck' => null,
        'IsActive' => null,
        'IsShareable' => null,
        'Disabled' => null,
        'DetectedCards' => null,
        'comment' => null,
        'Properties' => null,
        'MainProperties' => null,
        'BarCode' => null,
        'Description' => null,
        'Value' => null,

        'AccountOwner' => null,
        'UserID' => null,
        'UserName' => null,
        'UserAccountLogin' => null,
        'UserAccountEmail' => null,
        'AccountLevel' => null,
        'FamilyMemberName' => null,
        'CardNumber' => null,
        'Number' => null,
    ];

    public function alterTemplate(MapperContext $mapperContext)
    {
        parent::alterTemplate($mapperContext);

        $mapperContext->alterDataTemplateBy(self::DATA_TEMPLATE);
    }

    protected function mapAccount(MapperContext $mapperContext, $accountID, $accountFields)
    {
        $accountFields = parent::mapAccount($mapperContext, $accountID, $accountFields);

        $format = false;

        if (
            isset($accountFields['Properties']['BarCode']['Val']) && '' !== $accountFields['Properties']['BarCode']['Val']
            && isset($accountFields['Properties']['BarCodeType']['Val']) && '' !== $accountFields['Properties']['BarCodeType']['Val']
        ) {
            $format = $accountFields['Properties']['BarCodeType']['Val'];
            $barcode = $accountFields['Properties']['BarCode']['Val'];
        } elseif (
            isset($accountFields['BarCode']) && '' !== $accountFields['BarCode']
            && isset($accountFields['MainProperties']['Login']) && '' !== $accountFields['MainProperties']['Login']
        ) {
            $format = $accountFields['BarCode'];
            $barcode = $accountFields['MainProperties']['Login'];
        }

        if ($format && $format !== BAR_CODE_QR) {
            $barcodeCreator = new BarcodeCreator($this->root);
            $barcodeCreator->setFormat($format);
            $barcodeCreator->setNumber(str_replace(['-'], '', $barcode));

            try {
                $barcodeCreator->validate();
                $barcodeCreator->draw();
                $barcodeVal = $barcodeCreator->getBinaryText();
                $accountFields['BarCode'] = $barcodeVal;
            } catch (\Exception $e) {
                $accountFields['BarCode'] = null;
            }
        } elseif ($format === BAR_CODE_QR) {
            $accountFields['BarCode'] = null;
        }

        return $accountFields;
    }
}
