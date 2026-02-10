<?php

use AwardWallet\MainBundle\Controller\EmailSupportedController;
use AwardWallet\MainBundle\Loyalty\BackgroundCheck\AsyncTask;
use AwardWallet\MainBundle\Service\BackgroundCheckUpdater;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;

require_once __DIR__ . "/../lib/classes/TBaseSchema.php";

require_once "ProviderPhone.php";

class TProviderSchema extends TBaseSchema
{
    public const SQL_BOOL_NOT_SET = 3;
    private $Form;

    public function TProviderSchema()
    {
        global $arProviderKind, $arProviderState, $arDeepLinking, $barCodes;
        parent::TBaseSchema();
        $this->TableName = "Provider";
        $this->ListClass = "ProviderAdminList";
        $autoLoginField = [
            "Type" => "integer",
            "filterWidth" => 50,
            "Options" => [
                "" => "Unknown",
                0 => "No",
                1 => "Yes",
            ],
        ];
        $this->Fields = [
            "ProviderID" => [
                "Caption" => "id",
                "Type" => "integer",
                "InputAttributes" => " readonly",
                "filterWidth" => 30, ],
            "Name" => [
                "Type" => "string",
                "Size" => 200,
                "Cols" => 40,
                "Required" => true, ],
            "ShortName" => [
                "Type" => "string",
                "Size" => 40,
                "Cols" => 40,
                "Required" => true, ],
            "Code" => [
                "Type" => "string",
                "Size" => 20,
                "Cols" => 10,
                "filterWidth" => 70,
                "RegExp" => "#^[a-z]+[0-9a-z]*$#i",
                "RegExpErrorMessage" => "Only alphanumeric characters are allowed and first symbol must be a letter",
                "Required" => true, ],
            "ProgramName" => [
                "Caption" => "ProgramName",
                "Type" => "string",
                "Size" => 80,
                "Cols" => 30,
                "Required" => true, ],
            "DisplayName" => [
                "Caption" => "DisplayName",
                "Type" => "string",
                "Size" => 80,
                "Cols" => 30,
                "HTML" => true,
                "Required" => true, ],
            "Kind" => [
                "Type" => "integer",
                "InputType" => "select",
                "filterWidth" => 50,
                "Required" => true,
                "Options" => $arProviderKind, ],
            //			"Engine" => array(
            //				"Type" => "integer",
            //				"InputType" => "select",
            //				"InputAttributes" => "style='width: 60px;",
            //				"filterWidth" => 50,
            //				"Required" => True,
            //				"Options" => array(
            //					PROVIDER_ENGINE_CURL		=> 'Curl',
            //				) ),
            "EmailFormatKind" => [
                "Type" => "integer",
                "InputType" => "select",
                "filterWidth" => 50,
                "Required" => false,
                "Options" => ["" => ""] + EmailSupportedController::getArEmailFormatKind(), ],
            "LoginCaption" => [
                "Caption" => "Login caption",
                "Type" => "string",
                "Size" => 255,
                "Cols" => 15,
                "Note" => "You can use 'Login caption (Field note)' format to specify note. Text in parentheses goes to Note.",
                "Required" => false,
                "Nullable" => false,
            ],
            "LoginRequired" => [
                "Type" => "boolean",
                "Value" => 1,
            ],
            "Login2Caption" => [
                "Caption" => "Login2 caption",
                "Type" => "string",
                "Size" => 80,
                "Cols" => 15,
                "Required" => false,
            ],
            "Login2Required" => [
                "Type" => "boolean",
                "Value" => 1,
            ],
            "Login2AsCountry" => [
                "Type" => "boolean",
                "Value" => 1,
            ],
            "Login3Caption" => [
                "Caption" => "Login3 caption",
                "Type" => "string",
                "Size" => 80,
                "Cols" => 15,
                "Required" => false,
            ],
            "Login3Required" => [
                "Type" => "boolean",
                "Value" => 1,
            ],
            "PasswordCaption" => [
                "Caption" => "Password caption",
                "Type" => "string",
                "Size" => 80,
                "Cols" => 30,
                "Required" => false, ],
            'PasswordMinSize' => [
                'Caption' => 'Password min size',
                'Type' => 'integer',
                'Min' => 1,
                'Max' => 16,
                'Value' => 1,
                'Required' => false,
            ],
            'PasswordMaxSize' => [
                'Caption' => 'Password max size',
                'Type' => 'integer',
                'Max' => 256,
                'Value' => 80,
                'Required' => false,
            ],
            "PasswordRequired" => [
                "Type" => "boolean",
                "Value" => 1,
            ],
            "CanRetrievePassword" => [
                "Type" => "boolean",
                "Required" => false,
                "Options" => [
                    "0" => "No",
                    "1" => "Yes",
                ],
                "filterWidth" => 50,
            ],
            "CanChangePasswordServer" => [
                "Type" => "boolean",
                "Value" => 0,
            ],
            "CanChangePasswordClient" => [
                "Type" => "boolean",
                "Value" => 0,
            ],
            "Site" => [
                "Type" => "string",
                "Size" => 250,
                "Cols" => 50,
                "Required" => false,
                "RegExp" => "#^https?://.+#i", // @todo: refactor TBaseForm
                "RegExpErrorMessage" => "Protocol (http:// or https://) not found",
            ],
            "LoginURL" => [
                "Caption" => "Login URL",
                "Type" => "string",
                "Size" => 512,
                "Cols" => 50,
                "HTML" => true,
                "Required" => true,
                "RegExp" => "#^https?://.+#i",
                "RegExpErrorMessage" => "Protocol (http:// or https://) not found",
            ],
            "State" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => PROVIDER_COLLECTING_ACCOUNTS,
                "Options" => $arProviderState,
            ],
            "IsRetail" => [
                "Caption" => "Was inserted as retail provider",
                "Type" => "integer",
                "Required" => false,
                "Value" => 0,
                "InputType" => "checkbox",
                "InputAttributes" => " disabled ",
            ],
            "CollectingRequests" => [
                "Type" => "integer",
                "Required" => false,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "EnableDate" => [
                "Type" => "date",
                "Required" => false,
            ],
            "Accounts" => [
                "Type" => "integer",
                "filterWidth" => 30,
            ],
            "Questions" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "Note" => "Has security questions",
            ],
            "AutoLogin" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "filterWidth" => 50,
                "Options" => [
                    AUTOLOGIN_DISABLED => "Disabled",
                    AUTOLOGIN_SERVER => "Server",
                    AUTOLOGIN_MIXED => "Extension and server",
                    AUTOLOGIN_EXTENSION => "Extension only",
                ],
            ],
            "AutoLoginIE" => array_merge(["Caption" => "Server Auto Login IE"], $autoLoginField),
            "AutoLoginChrome" => array_merge(["Caption" => "Server Auto Login Chrome"], $autoLoginField),
            "AutoLoginSafari" => array_merge(["Caption" => "Server Auto Login Safari"], $autoLoginField),
            "AutoLoginFirefox" => array_merge(["Caption" => "Server Auto Login Firefox"], $autoLoginField),
            "MobileAutoLogin" => [
                "Caption" => "Mobile Autologin",
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "Options" => [
                    MOBILE_AUTOLOGIN_DISABLED => 'Disabled',
                    MOBILE_AUTOLOGIN_SERVER => 'Server',
                    MOBILE_AUTOLOGIN_EXTENSION => 'Mobile extension',
                    MOBILE_AUTOLOGIN_DESKTOP_EXTENSION => 'Desktop extension',
                ],
            ],
            "ItineraryAutologin" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "filterWidth" => 50,
                "Options" => [
                    ITINERARY_AUTOLOGIN_DISABLED => "Disabled",
                    ITINERARY_AUTOLOGIN_ACCOUNT => "Account",
                    ITINERARY_AUTOLOGIN_CONFNO => "Confirmation number",
                    ITINERARY_AUTOLOGIN_BOTH => "Account and confirmation number",
                ],
            ],
            "CanCheck" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckBalance" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckExpiration" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "No",
                    "1" => "Yes",
                    "2" => "Never expires",
                ],
            ],
            "ExpirationAlwaysKnown" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "InputType" => "checkbox",
            ],
            "DontSendEmailsSubaccExpDate" => [
                "Caption" => "Don't send emails about subaccounts expiration",
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "CanCheckItinerary" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckPastItinerary" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckConfirmation" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    CAN_CHECK_CONFIRMATION_NO => "No",
                    CAN_CHECK_CONFIRMATION_YES_SERVER => "Yes, Server",
                    CAN_CHECK_CONFIRMATION_YES_EXTENSION => "Yes, Extension",
                    CAN_CHECK_CONFIRMATION_YES_EXTENSION_AND_SERVER => "Yes, Extension and Server",
                ],
            ],
            "CanCheckCancelled" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckNoItineraries" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckHistory" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckFiles" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "filterWidth" => 40,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanCheckRewardAvailability" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "disabled",
                    "1" => "enabled for everyone",
                    "2" => "enabled only for AW",
                ],
            ],
            "CanCheckRaHotel" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanRegisterRewardAvailabilityAccount" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "RewardAvailabilityPriority" => [
                "Type" => "integer",
                "Value" => 0,
                "filterWidth" => 10,
            ],
            "RewardAvailabilityLockAccount" => [
                "Type" => "integer",
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "RewardAvailabilityResetAccountOnRestart" => [
                "Type" => "integer",
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "RewardAvailabilityNeedWarmedUpAccount" => [
                "Type" => "integer",
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CanTransferRewards" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "CanRegisterAccount" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "CanBuyMiles" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "CheckInBrowser" => [
                "Caption" => "Check using Extension (Desktop)",
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    CHECK_IN_SERVER => "false",
                    //					CHECK_IN_CLIENT => "Yes, keep data in browser",
                    //					CHECK_IN_MIXED => "Yes, copy data to database",
                    CHECK_IN_MIXED => "true",
                ],
            ],
            "CheckInMobileBrowser" => [
                "Caption" => "Check using Extension (Mobile)",
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "Options" => [
                    "0" => "false",
                    "1" => "true",
                ],
            ],
            "CheckInMobileV3" => [
                "Caption" => "Check using Extension V3 (Mobile)",
                "Type" => "integer",
                "InputType" => "checkbox",
                "Required" => true,
                "Value" => 0,
            ],
            "ExtensionV3ParserReady" => [
                "Caption" => "Extension V3 Parser Ready",
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "IsExtensionV3ParserEnabled" => [
                "Caption" => "Extension V3 Parser Enabled",
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "AutologinV3" => [
                "Caption" => "Autologin Extension V3 Enabled",
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "ConfNoV3" => [
                "Caption" => "Retrieve by Conf No Extension V3 Enabled",
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "CanMarkCoupons" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "CanParseCardImages" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "CanDetectCreditCards" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "WSDL" => [
                "Type" => "integer",
                "Caption" => "WSDL",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
                "filterWidth" => 30,
            ],
            "ExpirationDateNote" => [
                "Type" => "string",
                "Size" => 2000,
                "InputType" => "htmleditor",
                "HTML" => false,
                "Required" => false,
            ],
            "ExpirationUnknownNote" => [
                "Type" => "string",
                "Size" => 2000,
                "InputType" => "htmleditor",
                "HTML" => true,
                "Required" => false,
            ],
            "TradeText" => [
                "Type" => "string",
                "Size" => 4000,
                "InputType" => "htmleditor",
                "HTML" => true,
                "Required" => false,
            ],
            "TradeMin" => [
                "Type" => "integer",
                "Required" => true,
                "Min" => 0,
                "Value" => 0,
                "Note" => "0 - trade disabled",
            ],
            "RedirectByHTTPS" => [
                "Caption" => "Redirect By HTTPS",
                "Type" => "integer",
                "Required" => true,
                "Value" => 1,
                "InputType" => "checkbox",
            ],
            "DefaultRegion" => [
                "Type" => "string",
                "Required" => false,
                "Size" => 80,
            ],
            "AllowFloat" => [
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "BalanceFormat" => [
                "Type" => "string",
                "Required" => false,
                "Size" => 60,
            ],
            "CustomDisplayName" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
            ],
            "Difficulty" => [
                "Type" => "integer",
                "Required" => true,
                "Options" => [
                    "1" => "Easy",
                    "2" => "Normal",
                    "3" => "Hard",
                ],
                "Value" => 1,
            ],
            "ImageURL" => [
                "Caption" => "Image URL",
                "Type" => "string",
                "Size" => 512,
                "Cols" => 50,
                "HTML" => true,
                "Required" => false,
            ],
            "ClickURL" => [
                "Caption" => "Click URL",
                "Note" => "https:// only",
                "Type" => "string",
                "Size" => 512,
                "Cols" => 50,
                "HTML" => true,
                "RegExp" => "#^https://.+#i", // @todo: refactor TBaseForm
                "Required" => false,
            ],
            "FAQ" => [
                "Type" => "integer",
                "Caption" => "FAQ",
                "Note" => "This FAQ number will be displayed in case of error",
                "Required" => false,
            ],
            "ProviderGroup" => [
                "Type" => "string",
                "Size" => 20,
                "Cols" => 20,
                "Required" => false,
            ],
            "BarCode" => [
                "Type" => "string",
                "Size" => 20,
                "Options" => ["" => "None"] + $barCodes,
                "Required" => false, ],
            "Corporate" => [
                "Caption" => "Corporate program",
                "Type" => "integer",
                "Required" => true,
                "Value" => 0,
                "InputType" => "checkbox",
            ],
            "AllianceID" => [
                "Caption" => "Alliance",
                "Type" => "integer",
                "Options" => ["" => ""] + SQLToArray("SELECT AllianceID, Name FROM Alliance ORDER BY Name", "AllianceID", "Name"),
            ],
            "Currency" => [
                "Type" => "integer",
                "Options" => ["" => ""] + SQLToArray("SELECT CurrencyID, Name FROM Currency ORDER BY CurrencyID", "CurrencyID", "Name"),
                "Required" => true,
            ],
            "Goal" => [
                "Type" => "integer",
                "Required" => false,
            ],
            "DeepLinking" => [
                "Type" => "integer",
                "Value" => DEEP_LINKING_UNKNOWN,
                "filterWidth" => 50,
                "Options" => $arDeepLinking,
            ],
            "Note" => [
                "Type" => "string",
                "Size" => 4000,
                "InputType" => "htmleditor",
                "Required" => false,
                "HTML" => true,
            ],
            "EliteLevelsCount" => [
                "Caption" => "Elite Levels Count",
                "Type" => "integer",
                "Size" => 5,
                "Cols" => 5,
                "filterWidth" => 30,
                "Required" => false, ],
            "CalcEliteLevelExpDate" => [
                "Caption" => "Calculate Elite Level Expiration Date",
                "Type" => "boolean",
                "Size" => 5,
                "Cols" => 5,
                "filterWidth" => 30,
                "Required" => false, ],
            "EliteProgramComment" => [
                "Type" => "string",
                "InputType" => "textarea",
                "Size" => 2000,
                "InputAttributes" => "style='width: 400px;'",
            ],
            "Phones" => [
                "Type" => "string",
                "Database" => false,
            ],
            "HasSupportPhones" => [
                "Type" => "boolean",
                "Value" => "1",
            ],
            "KeyWords" => [
                "Caption" => "KeyWords",
                "Type" => "string",
                "InputType" => "textarea",
                "Required" => true,
                "Note" => "Words separated by a comma, regexp separated by a comma ('/coupons?/')",
            ],
            "StopKeyWords" => [
                "Caption" => "Stop KeyWords",
                "Type" => "string",
                "InputType" => "textarea",
                "Required" => false,
                "Note" => "Words separated by a comma, regexp separated by a comma ('/coupons?/')",
            ],
            "RequestsPerMinute" => [
                "Type" => "integer",
                "Required" => false,
                "Note" => "You can specify negative values for Accounts per Minute, for example '-2' means 2 accounts per minute
                Accounts Per Minutes is always applied, Requests Per Minute respects ThrottleBelowPriority",
            ],
            "ThrottleAllChecks" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "Note" => "If necessary throttle all checks",
            ],
            "CanReceiveEmail" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "Note" => "Can we parse emails for this provider",
            ],
            "CanScanEmail" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "filterWidth" => "40",
            ],
            "PlanEmail" => [
                "Type" => "string",
                "Size" => 120,
                "Note" => "From what email receive plans, regexp",
            ],
            "IsEarningPotential" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "Note" => "Enable credit card analytics: /transactions/ etc",
            ],
            "InternalNote" => [
                "Type" => "string",
                "Size" => 2000,
                "InputType" => "htmleditor",
                "HTML" => true,
                "Required" => false,
                "OnGetRequired" => [$this, "isInternalNoteRequired"],
            ],
            "Category" => [
                "Caption" => "Category",
                "Type" => "integer",
                "Value" => 3,
                "filterWidth" => 40,
            ],
            "IATACode" => [
                "Caption" => "IATA Code",
                "Type" => "string",
                "Size" => 2,
                "Cols" => 10,
                "filterWidth" => 20,
                "Note" => "AirLine IATA Code",
            ],
            "CheckInReminderOffsets" => [
                "Caption" => "Check-in reminder offsets",
                "Type" => "string",
                "InputType" => "textarea",
                "Size" => 2000,
                "HTML" => true,
                "Cols" => 100,
                "Note" => 'in hours',
                "Required" => true,
                "Value" => json_encode([
                    'push' => [1, 4, 24],
                    'mail' => [24],
                ], JSON_PRETTY_PRINT),
            ],
            "IgnoreEmailsFromPartners" => [
                "Caption" => "Ignore Emails From Partners",
                "Type" => "string",
                "InputType" => "textarea",
                "Required" => false,
                "RegExp" => "#^[\w\-,\s]+$#i",
                "RegExpErrorMessage" => "Only letters, digits, commas, dashes",
                "Note" => "Words separated by a comma",
                "Nullable" => false,
                "Value" => "",
            ],
            "Description" => [
                "Type" => "string",
                "Size" => 2000,
                "InputType" => "htmleditor",
                "HTML" => true,
                "Required" => false,
            ],
            'BlogPostID' => [
                'Caption' => "Blog Post IDs",
                'Type' => 'string',
                'Size' => 255,
                'Required' => false,
                'Note' => 'Use a comma to separate',
            ],
            'BlogTagsID' => [
                'Caption' => "Blog Tag IDs",
                'Type' => 'string',
                'Size' => 255,
                'Required' => false,
                'Note' => 'Use a comma to separate',
            ],
            'BlogIdsMilesPurchase' => [
                'Caption' => "Blog IDs for Miles Purchase",
                'Type' => 'string',
                'Size' => 250,
                'Required' => false,
                'Note' => 'Blog ID that talks about purchasing miles or points for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
            ],
            'BlogIdsMilesTransfers' => [
                'Caption' => "Blog IDs for Miles Transfers",
                'Type' => 'string',
                'Size' => 250,
                'Required' => false,
                'Note' => 'Blog ID that talks about transferring miles or points for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
            ],
            'BlogIdsPromos' => [
                'Caption' => "Blog IDs of Promos",
                'Type' => 'string',
                'Size' => 250,
                'Required' => false,
                'Note' => 'Comma separated list of blog IDs that talk about various promos for this provider',
            ],
            'BlogIdsMileExpiration' => [
                'Caption' => "Blog IDs of Mile Expirations",
                'Type' => 'string',
                'Size' => 250,
                'Required' => false,
                'Note' => 'Blog ID that talks about point or mile expiration policy for this provider, if more than one, make them comma-separated but the first one will be used in most cases',
            ],
            'TransactionPatterns' => [
                'Caption' => "Ignore transactions to calculate total",
                'Type' => 'string',
                "InputType" => 'textarea',
                'Required' => false,
                'Note' => 'One key phrase per line<br>If the line starts with # then it is a regular expression<br>If the string starts with +, then consider this transaction positive. You can use the combination +#words<br>In other cases, the transaction description must contain a phrase from the line',
            ],
            'AwardChangePolicy' => [
                'Caption' => "Award Change Policy",
                'Type' => 'string',
                'Size' => 5000,
                'InputType' => 'htmleditor',
                'htmleditorCustomConfig' => [
                    'coreStyles_bold' => ['element' => 'strong'],
                ],
                'HTML' => true,
                'Required' => false,
                'Note' => sprintf(
                    'You can test how it looks in the email <a href="%s" target="_blank">here</a>',
                    '/manager/emailViewer?id=awardwallet_mainbundle_frameworkextension_mailer_template_itinerary_bestmileagedeals_choice'
                ),
            ],
        ];
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();
        unset($arFields["Difficulty"]);
        unset($arFields["CanCheck"]);
        unset($arFields["ShortName"]);
        unset($arFields["Name"]);
        unset($arFields["ProgramName"]);
        unset($arFields["LoginURL"]);
        unset($arFields["PasswordCaption"]);
        unset($arFields['PasswordMinSize']);
        unset($arFields['PasswordMaxSize']);
        unset($arFields["LoginCaption"]);
        unset($arFields["Login2Caption"]);
        unset($arFields["Login2AsCountry"]);
        unset($arFields["Login3Caption"]);
        unset($arFields["PasswordRequired"]);
        unset($arFields["OneTravelCode"]);
        unset($arFields["OneTravelName"]);
        unset($arFields["OneTravelID"]);
        unset($arFields["ExpirationDateNote"]);
        unset($arFields["ExpirationUnknownNote"]);
        unset($arFields["RenewNote"]);
        unset($arFields["CanCheckItinerary"]);
        unset($arFields["CanCheckConfirmation"]);
        unset($arFields["CanCheckExpiration"]);
        unset($arFields["ExpirationAlwaysKnown"]);
        unset($arFields["CanMarkCoupons"]);
        unset($arFields["TradeText"]);
        unset($arFields["TradeMin"]);
        unset($arFields["RedirectByHTTPS"]);
        unset($arFields["DefaultRegion"]);
        unset($arFields["CanCheckBalance"]);
        unset($arFields["AllowFloat"]);
        unset($arFields["BalanceFormat"]);
        unset($arFields["CustomDisplayName"]);
        unset($arFields["ImageURL"]);
        unset($arFields["ClickURL"]);
        unset($arFields["FAQ"]);
        unset($arFields["ProviderGroup"]);
        unset($arFields["BarCode"]);
        unset($arFields["MobileAutoLogin"]);
        unset($arFields["Corporate"]);
        unset($arFields["AllianceID"]);
        unset($arFields["Note"]);
        unset($arFields["AAADiscount"]);
        unset($arFields["CheckInBrowser"]);
        unset($arFields["KeyWords"]);
        unset($arFields["StopKeyWords"]);
        unset($arFields["RequestsPerMinute"]);
        unset($arFields["InternalNote"]);
        unset($arFields['CalcEliteLevelExpDate']);
        unset($arFields['EliteProgramComment']);
        unset($arFields['CanReceiveEmail']);
        unset($arFields['AutoLoginIE']);
        unset($arFields['AutoLoginChrome']);
        unset($arFields['AutoLoginSafari']);
        unset($arFields['AutoLoginFirefox']);
        unset($arFields['CanCheckCancelled']);
        unset($arFields['CanCheckNoItineraries']);
        unset($arFields['ItineraryAutologin']);
        unset($arFields['Questions']);
        unset($arFields['Currency']);
        unset($arFields['CanCheckHistory']);
        unset($arFields['DontSendEmailsSubaccExpDate']);
        unset($arFields['Goal']);
        unset($arFields['HasSupportPhones']);
        unset($arFields['CanTransferRewards']);
        unset($arFields['CanBuyMiles']);
        unset($arFields['CanRegisterAccount']);
        unset($arFields['EnableDate']);
        unset($arFields['CanCheckFiles']);
        unset($arFields['CanCheckRewardAvailability']);
        unset($arFields['CanCheckRaHotel']);
        unset($arFields['CanRegisterRewardAvailabilityAccount']);
        unset($arFields['RewardAvailabilityLockAccount']);
        unset($arFields['RewardAvailabilityResetAccountOnRestart']);
        unset($arFields['RewardAvailabilityNeedWarmedUpAccount']);
        unset($arFields['RewardAvailabilityPriority']);
        unset($arFields['Category']);
        unset($arFields['CheckInMobileBrowser']);
        unset($arFields['CanScanEmail']);
        unset($arFields['IsEarningPotential']);
        $arFields["IATACode"]["Caption"] = "IATA";
        $arFields["CanRetrievePassword"]["Caption"] = "CanRetPass";
        $arFields["EliteLevelsCount"]["Caption"] = "Elite Levels";
        $arFields["CanParseCardImages"]["Caption"] = "Card Images";
        $arFields["CanDetectCreditCards"]["Caption"] = "CC Detect";

        foreach ($arFields as $key => $field) {
            $arFields[$key]["InplaceEdit"] = false;
        }
        $arFields["WSDL"]["InplaceEdit"] = true;
        //		$arFields["Currency"]["InplaceEdit"] = true;
        //		$arFields["Engine"]["InplaceEdit"] = true;
        unset($arFields['PlanEmail']);
        unset($arFields['CheckInReminderOffsets']);
        unset($arFields['CollectingRequests']);
        unset($arFields['IsRetail']);
        unset($arFields['LoginRequired']);
        unset($arFields['Login2Required']);
        unset($arFields['Login3Required']);
        unset($arFields['CanCheckPastItinerary']);
        unset($arFields['CanChangePasswordServer']);
        unset($arFields['CanChangePasswordClient']);
        unset($arFields['IgnoreEmailsFromPartners']);
        unset($arFields['Description']);
        unset($arFields['BlogTagsID']);
        unset($arFields['BlogPostID']);
        unset($arFields['BlogIdsMilesPurchase']);
        unset($arFields['BlogIdsMilesTransfers']);
        unset($arFields['BlogIdsPromos']);
        unset($arFields['BlogIdsMileExpiration']);
        unset($arFields['IsExtensionV3ParserEnabled']);
        unset($arFields['AutologinV3']);
        unset($arFields['TransactionPatterns']);
        unset($arFields['AwardChangePolicy']);

        return $arFields;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        if (ArrayVal($_SERVER, 'REMOTE_USER') == 'points') {
            $list->ShowImport = false;
            $list->AllowDeletes = false;
            $list->CanAdd = false;
            $list->MultiEdit = false;
        } else {
            $list->InplaceEdit = true;
        }
        $list->DefaultSort = "Accounts";
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        $fields['Phones']['InputType'] = 'html';
        $fields['Phones']['HTML'] = TProviderPhoneSchema::getPhonesLink(intval(ArrayVal($_GET, 'ID')), null);
        unset($fields['Accounts']);

        ArrayInsert($fields, "LoginURL", true, [
            "Countries" => [
                "Caption" => "Countries",
                "Type" => "string",
                "Manager" => ($f = function () {
                    require_once __DIR__ . "/../lib/classes/TTableLinksFieldManager.php";

                    $manager = new TTableLinksFieldManager();
                    $manager->TableName = 'ProviderCountry';
                    $manager->UniqueFields = ['CountryID'];
                    $manager->CanEdit = true;
                    $manager->Fields = [
                        "CountryID" => [
                            "Caption" => "Country",
                            "Type" => "integer",
                            "Options" => SQLToArray(
                                "select `CountryID`, CONCAT(`Name`, ifnull(concat(' (', Code, ')'), '')) as `Name` from Country",
                                "CountryID",
                                "Name"
                            ),
                            "Required" => true,
                        ],
                        "Site" => [
                            "Type" => "string",
                            "Required" => true,
                            'Value' => '',
                            'Nullable' => false,
                            "RegExp" => "#^https?://.+#i",
                            "RegExpErrorMessage" => "Protocol (http:// or https://) not found",
                        ],
                        "LoginURL" => [
                            "Caption" => "Login URL",
                            "Type" => "string",
                            'Value' => '',
                            "Required" => false,
                            'Nullable' => false,
                            "RegExp" => "#^https?://.+#i",
                            "RegExpErrorMessage" => "Protocol (http:// or https://) not found",
                        ],
                        "LoginCaption" => [
                            "Type" => "string",
                            "Required" => false,
                            'Value' => '',
                            'Nullable' => false,
                        ],
                    ];

                    return $manager;
                }) ? $f() : null, ], ]
        );

        return $fields;
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->Fields['CanRetrievePassword']['Options'] = array_merge(
            ['' => 'Not set'],
            $form->Fields['CanRetrievePassword']['Options']
        );

        if ($form->ID == 0) {
            unset($form->Fields['ProviderID']);
        }
        $form->Uniques = [
            [
                "Fields" => ["Code"],
                "ErrorMessage" => "Provider with this Code already exists. Please choose another Code.",
            ],
            [
                "Fields" => ["DisplayName"],
                "ErrorMessage" => "Provider with this Display Name already exists. Please choose another Display Name.",
            ],
        ];

        if (($form->ID > 0)
        && (Lookup("Account", "ProviderID", "ProviderID", $form->ID) > 0)) {
            $form->Fields['Code']['InputAttributes'] = 'readonly';
            $form->Fields['Code']['Note'] = 'There are live accounts for this provider, you can not change Code';
        }

        if (ArrayVal($_SERVER, 'REMOTE_USER') == 'points') {
            $form->SubmitButtonCaption = "Cancel";
            $form->ReadOnly = true;
        }

        $form->OnCheck = [$this, "formCheck", &$form];
        $form->OnSave = [$this, "formSaved", &$form];
        $this->Form = &$form;
    }

    public function formCheck($objForm)
    {
        if ($this->Form->ID != 0) {
            $statuses = [
                PROVIDER_IN_DEVELOPMENT,
                PROVIDER_ENABLED,
                PROVIDER_CHECKING_OFF,
                PROVIDER_CHECKING_EXTENSION_ONLY,
                PROVIDER_CHECKING_WITH_MAILBOX,
                PROVIDER_DISABLED,
                PROVIDER_WSDL_ONLY,
            ];

            if ($this->Form->Fields['State']['Value'] != $this->Form->Fields['State']['OldValue']
                && in_array($this->Form->Fields['State']['Value'], $statuses)
                && in_array($this->Form->Fields['State']['OldValue'], $statuses)
                && $this->Form->Fields['InternalNote']['Value'] == $this->Form->Fields['InternalNote']['OldValue']) {
                return 'Please make an entry in the field "Internal Note"';
            }
        }

        foreach ([['KeyWords', true], ['StopKeyWords', false]] as [$keywordsFieldName, $checkForEmptiness]) {
            if (
                $checkForEmptiness
                && empty($this->Form->Fields[$keywordsFieldName]['Value'])
            ) {
                return "Please make an entry in the field '{$keywordsFieldName}'";
            }

            // check keywords for stop-words
            $invalidKeywords = [];
            $stopwordRegexp = getSymfonyContainer()->get('aw.card_image.regexp_compiler')->compileStopwords();

            foreach (explode(',', $this->Form->Fields[$keywordsFieldName]['Value']) as $keyword) {
                if (preg_match($stopwordRegexp, $keyword = trim($keyword))) {
                    $invalidKeywords[] = $keyword;
                }
            }

            if ($invalidKeywords) {
                return "Invalid '{$keywordsFieldName}' detected: " . implode(', ', $invalidKeywords);
            }
        }

        if (isset($this->Form->Fields['CheckInReminderOffsets']['Value'])) {
            $decoded = @json_decode($this->Form->Fields['CheckInReminderOffsets']['Value'], true);

            if (
                !is_array($decoded)
                || !isset($decoded['mail'], $decoded['push'])
                || !is_array($decoded['mail'])
                || !is_array($decoded['push'])
                || (count($decoded['mail']) !== count(array_filter($decoded['mail'], 'is_numeric')))
                || (count($decoded['push']) !== count(array_filter($decoded['push'], 'is_numeric')))
            ) {
                return 'Invalid CheckInReminderOffsets';
            }
        }

        if (
            1 == $this->Form->Fields['CollectingRequests']['Value']
            && !in_array($this->Form->Fields['State']['Value'], [PROVIDER_RETAIL, PROVIDER_DISABLED, PROVIDER_HIDDEN])
        ) {
            return 'Collecting Requests provider must have State ∈ (Retail, Disabled)';
        }

        if (isset($this->Form->Fields['IgnoreEmailsFromPartners']['Value'])) {
            $this->Form->Fields['IgnoreEmailsFromPartners']['Value'] = trim($this->Form->Fields['IgnoreEmailsFromPartners']['Value']);
            $invalidPartners = [];

            foreach (explode(',', $this->Form->Fields['IgnoreEmailsFromPartners']['Value']) as $partner) {
                if (preg_match("#^[\w\-]+$#", $partner = trim($partner)) === 0) {
                    $invalidPartners[] = $partner;
                }
            }

            if ($invalidPartners) {
                return "Invalid IgnoreEmailsFromPartners detected: " . implode(', ', $invalidPartners);
            }
        }

        return null;
    }

    public function formSaved($objForm)
    {
        global $Connection;

        if ($objForm->ID) {
            $oldBackgroundCheck = BackgroundCheckUpdater::calcBackgroundCheck((int) $objForm->Fields['State']['OldValue'], (bool) $objForm->Fields['CanCheck']['OldValue']);
            $backgroundCheck = BackgroundCheckUpdater::calcBackgroundCheck((int) $objForm->Fields['State']['Value'], (bool) $objForm->Fields['CanCheck']['Value']);

            if ($oldBackgroundCheck != $backgroundCheck) {
                $task = new AsyncTask($objForm->ID);
                getSymfonyContainer()->get(Process::class)->execute($task);
            }
        }

        if ($objForm->IsInsert) {
            $Connection->Execute(UpdateSQL('Provider', ['ProviderID' => $objForm->ID], ['CreationDate' => 'NOW()']));
        }

        if ($objForm->Fields['State']['OldValue'] != PROVIDER_ENABLED && $objForm->Fields['State']['Value'] == PROVIDER_ENABLED) {
            $Connection->Execute(UpdateSQL('Provider', ['ProviderID' => $objForm->ID], ['EnableDate' => 'NOW()']));
        }

        if (!$objForm->IsInsert
            && $objForm->Fields['CanCheckRewardAvailability']['OldValue'] !== $objForm->Fields['CanCheckRewardAvailability']['Value']
            // for juicymiles 0, 2 - all is disabled
            && ($objForm->Fields['CanCheckRewardAvailability']['OldValue'] + $objForm->Fields['CanCheckRewardAvailability']['Value'] !== 2)
        ) {
            getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\RA\RewardAvailabilityStatus::class)->alertCanCheckRewardAvailability($objForm->Fields['Code']['Value'], $objForm->Fields['CanCheckRewardAvailability']['Value']);
        }

        self::triggerDatabaseUpdate();
    }

    public static function triggerDatabaseUpdate(?array $data = null)
    {
        if (empty(getSymfonyContainer()->getParameter('jenkins_token'))) {
            return;
        }

        $userpwd = 'jenkins:' . getSymfonyContainer()->getParameter('jenkins_token');

        $curl = curl_init();
        $data['voteMailer'] = empty($data) ? http_build_query(['Provider' => 0, 'Type' => '']) : http_build_query($data);

        $url = 'https://jenkins.awardwallet.com/job/provider-tables-sync/buildWithParameters';
        curl_setopt($curl, CURLOPT_USERPWD, $userpwd);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $result = curl_exec($curl);
        curl_close($curl);

        getSymfonyContainer()->get("logger")->info("jenkins call result: " . $result, is_array($data) ? $data : []);
    }

    public function GetImportKeyFields()
    {
        return ["Code"];
    }

    public function isInternalNoteRequired($fieldName, $field)
    {
        if ($this->Form->ID != 0) {
            return $this->Form->Fields['State']['Value'] != $this->Form->Fields['State']['OldValue'] || !empty($this->Form->Fields['InternalNote']['OldValue']);
        } else {
            return false;
        }
    }
}
