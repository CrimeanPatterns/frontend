<?php

use AwardWallet\Common\PasswordCrypt\PasswordEncryptor;

require_once __DIR__ . "/../lib/schema/BaseUserAdmin.php";

require_once __DIR__ . "/PasswordVault.php";

global $objCart;

class TUserAdminSchema extends TBaseUserAdminSchema
{
    private $bookerFields = [];
    private $businessFields = [];
    private $bookerGroupId;

    public function __construct()
    {
        parent::__construct();
        $this->Fields['InBeta'] = [
            "Type" => "boolean",
        ];
        $this->Fields['BetaApproved'] = [
            "Type" => "boolean",
        ];
        $this->Fields['Promo500k'] = [
            "Type" => "boolean",
            "Caption" => "Promo 13 years",
        ];
        $this->Fields['EnableMobileLog'] = [
            "Type" => "boolean",
        ];
        $this->Fields['Mismanagement'] = [
            "Caption" => "Mismanagement of users",
            "Type" => "boolean",
        ];
        $this->Fields['BetaInvitesCount'] = [
            "Type" => "integer",
        ];
        $this->Fields['PlusExpirationDate'] = [
            "Type" => "date",
            "Required" => false,
        ];
        $this->Fields['Fraud'] = [
            "Type" => "boolean",
        ];
    }

    public function GetListFields()
    {
        $arFields = parent::GetListFields();
        $arFields['ProvidersCount'] = [
            "Type" => "integer",
            "Caption" => "LPs",
            "Sort" => "MaxBalance DESC",
        ];
        $arFields['PlansCount'] = [
            "Type" => "integer",
            "Caption" => "Plans",
            "Sort" => "PlansCount DESC",
        ];
        $arFields['AccountLevel'] = [
            "Type" => "integer",
            "Caption" => "Account level",
            "Options" => [
                ACCOUNT_LEVEL_FREE => "Free",
                ACCOUNT_LEVEL_AWPLUS => "AW Plus",
                ACCOUNT_LEVEL_BUSINESS => "Business",
            ],
        ];
        $arFields['EmailVerified']['filterWidth'] = 30;
        ArrayInsert($arFields, "BetaApproved", true, ["LastUserAgent" => [
            "Type" => "string",
            "Caption" => "Browser",
            "HTML" => true,
            "Options" => [
                "Firefox" => "FF",
                "Chrome" => "Chrome",
                "Version" => "Safari",
                "MSIE" => "IE",
            ],
        ]]);
        ArrayInsert($arFields, 'Email', true, [
            'ValidMailboxesCount' => [
                'Caption' => 'Mailboxes',
                'Type' => 'string',
                'Sort' => 'ValidMailboxesCount DESC',
                'filterWidth' => 20,
            ],
        ]);
        unset($arFields["EmailRewardsHeader"]);
        unset($arFields["CheckinReminder"]);
        unset($arFields["AutoGatherPlans"]);
        unset($arFields["RegionalHeader"]);
        unset($arFields["DateFormat"]);
        unset($arFields["ThousandsSeparator"]);
        unset($arFields["PrimaryFunctionality"]);
        unset($arFields["EmailNewPlans"]);
        unset($arFields["EmailPlansChanges"]);
        unset($arFields["EmailRewards"]);
        unset($arFields["EmailTCSubscribe"]);
        unset($arFields["EmailProductUpdates"]);
        unset($arFields["EmailOffers"]);
        unset($arFields["EmailFamilyMemberAlert"]);
        unset($arFields["HomeAirport"]);
        unset($arFields["LastScreenWidth"]);
        unset($arFields["TravelHeader"]);
        unset($arFields["SavePassword"]);
        unset($arFields["InBeta"]);
        unset($arFields["EnableMobileLog"]);
        unset($arFields["BetaApproved"]);
        unset($arFields["Mismanagement"]);
        unset($arFields["2FactorAuth"]);
        unset($arFields["BetaInvitesCount"]);
        unset($arFields["PlusExpirationDate"]);
        unset($arFields["Promo500k"]);
        unset($arFields["Fraud"]);

        return $arFields;
    }

    public function GetFormFields()
    {
        global $Interface;
        $authChecker = getSymfonyContainer()->get('security.authorization_checker');

        if (!$authChecker->isGranted('ROLE_MANAGE_EDIT_USER')) {
            DieTrace("Access denied");
        }

        $cameFrom = ["" => "unknown"] + SQLToArray("SELECT SiteAdID, concat(SiteAdID, ' - ', Description, if(BookerID, concat(' [booker: ', BookerID, ']'), '')) as Name FROM SiteAd order by BookerID desc", 'SiteAdID', 'Name');
        $ownedBusiness = $defaultBooker = ["" => ""] + SQLToArray("select UserID as BookerID, ServiceName from AbBookerInfo order by ServiceName", "BookerID", "ServiceName");
        $arFields = parent::GetFormFields();

        if (!$authChecker->isGranted('ROLE_MANAGE_EDIT_USER')) {
            DieTrace("Access denied");
        }

        $nID = ArrayVal($_GET, 'ID', 0);
        $this->getBookerFields($nID);
        $this->getBusinessFields($nID);
        $this->getGroupId('Booking business');
        ArrayInsert(
            $arFields,
            'LastScreenHeight',
            true,
            [
                "CameFrom" => [
                    "Caption" => "Came from",
                    "Type" => "integer",
                    "Options" => $cameFrom],
                "DefaultBookerID" => [
                    "Caption" => "Default Booker",
                    "Type" => "integer",
                    "Options" => $defaultBooker],
                "OwnedByBusinessID" => [
                    "Caption" => "Belongs to Business",
                    "Type" => "integer",
                    "Options" => $ownedBusiness],
                "OwnedByManagerID" => [
                    "Caption" => "Belongs to Business Manager",
                    "Type" => "integer"],
            ]
        );

        ArrayInsert($arFields, 'GroupMembership', true, [
            'Booker_hr1' => [
                'IncludeCaption' => false,
                'Type' => 'html',
                'Database' => false,
                'HTML' => "
					<tr id=\"trBooker_hr1\"><td colspan=4><hr /></td></tr>
				",
            ],
            'Booker_Price' => [
                'Caption' => 'Price',
                'Type' => 'float',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'Price', null),
                'Database' => false,
            ],
            'Booker_PricingDetails' => [
                'Caption' => 'Pricing Details',
                'Type' => 'string',
                "InputType" => "textarea",
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'PricingDetails', null),
                'Database' => false,
                'Size' => 5000,
            ],
            'Booker_ServiceName' => [
                'Caption' => 'Service Name',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServiceName', null),
                'Database' => false,
                'Size' => 120,
            ],
            'Booker_ServiceShortName' => [
                'Caption' => 'Service Short Name',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServiceShortName', null),
                'Database' => false,
                'Size' => 20,
            ],
            'Booker_MerchantName' => [
                'Caption' => 'Merchant Name',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'MerchantName', null),
                'Database' => false,
                'Size' => 100,
            ],
            'Booker_Address' => [
                'Caption' => 'Address',
                "Type" => "string",
                "Size" => 250,
                "InputType" => "textarea",
                "HTML" => true,
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'Address', null),
                'Database' => false,
            ],
            'Booker_ServiceURL' => [
                'Caption' => 'Service URL',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServiceURL', null),
                'Database' => false,
                'Size' => 250,
            ],
            'Booker_OutboundPercent' => [
                'Caption' => 'Outbound Percent',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'OutboundPercent', null),
                'Database' => false,
            ],
            'Booker_InboundPercent' => [
                'Caption' => 'Inbound Percent',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'InboundPercent', null),
                'Database' => false,
            ],
            'Booker_Discount' => [
                'Caption' => 'Discount',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'Discount', null),
                'Database' => false,
            ],
            'Booker_SmtpServer' => [
                'Caption' => 'Smtp Server',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SmtpServer', null),
                'Database' => false,
                'Size' => 250,
            ],
            'Booker_SmtpPort' => [
                'Caption' => 'Smtp Port',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SmtpPort', null),
                'Database' => false,
            ],
            'Booker_SmtpUseSsl' => [
                'Caption' => 'Smtp Use Ssl',
                'Type' => 'boolean',
                'InputType' => 'checkbox',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SmtpUseSsl', null),
                'Database' => false,
            ],
            'Booker_SmtpUsername' => [
                'Caption' => 'Smtp Username',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SmtpUsername', null),
                'Database' => false,
                'Size' => 250,
            ],
            'Booker_SmtpPassword' => [
                'Caption' => 'Smtp Password',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SmtpPassword', null),
                'Database' => false,
                'Size' => 250,
            ],
            'Booker_FromEmail' => [
                'Caption' => 'FROM Email Address',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'FromEmail', null),
                'Database' => false,
                'Size' => 80,
            ],
            'Booker_ImapMailbox' => [
                'Caption' => 'Imap mailbox',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ImapMailbox', null),
                'Database' => false,
                'Size' => 200,
                'Note' => '{imap.gmail.com:993/ssl}INBOX for gmail accounts',
            ],
            'Booker_ImapLogin' => [
                'Caption' => 'Imap Login',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ImapLogin', null),
                'Database' => false,
                'Size' => 200,
                'Note' => 'typically Email, same as FROM',
            ],
            'Booker_ImapPassword' => [
                'Caption' => 'Imap Password',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ImapPassword', null),
                'Database' => false,
                'Size' => 80,
                'Note' => '',
            ],
            'Booker_Greeting' => [
                'Caption' => 'Greeting',
                "Type" => "string",
                "Size" => 10000,
                "InputType" => "textarea",
                "HTML" => true,
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'Greeting', null),
                'Database' => false,
            ],
            'Booker_AutoReplyMessage' => [
                'Caption' => 'AutoReplyMessage',
                "Type" => "string",
                "Size" => 10000,
                "InputType" => "textarea",
                "HTML" => true,
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'AutoReplyMessage', null),
                'Database' => false,
            ],
            'Booker_SiteAdID' => [
                'Caption' => 'Ref',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'SiteAdID', null),
                "Options" => SQLToArray("SELECT SiteAdID, concat(SiteAdID, ' - ', Description, if(BookerID, concat(' [booker: ', BookerID, ']'), '')) as Name FROM SiteAd order by BookerID desc", 'SiteAdID', 'Name'),
                'Database' => false,
            ],
            'Booker_CurrencyID' => [
                'Caption' => 'Currency',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'CurrencyID', null),
                "Options" => SQLToArray("SELECT CurrencyID, concat_ws(' ', Name, Code) as Name FROM Currency", 'CurrencyID', 'Name'),
                'Database' => false,
            ],
            'Booker_CreditCardPaymentType' => [
                'Caption' => 'Payment Gateway',
                'Type' => 'integer',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'CreditCardPaymentType', null),
                'Database' => false,
                'Options' => [
                    \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_CREDITCARD => 'Paypal',
                    \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_RECURLY => 'Recurly',
                    \AwardWallet\MainBundle\Entity\Cart::PAYMENTTYPE_STRIPE => 'Stripe',
                ],
            ],
            'Booker_IncludeCreditCardFee' => [
                'Caption' => 'Include Credit Card Fee',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'IncludeCreditCardFee', null),
                'Database' => false,
            ],
            'Booker_PayPalPassword' => [
                'Caption' => 'PayPal Password or Recurly/Stripe API Key',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'PayPalPassword', null),
                'Database' => false,
                'Size' => 80,
                'Note' => '',
            ],
            'Booker_PayPalClientId' => [
                'Caption' => 'PayPal REST API ClientId',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'PayPalClientId', null),
                'Database' => false,
                'Size' => 80,
                'Note' => '',
            ],
            'Booker_PayPalSecret' => [
                'Caption' => 'PayPal REST API Secret',
                'Type' => 'string',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'PayPalSecret', null),
                'Database' => false,
                'Size' => 80,
                'Note' => '',
            ],
            'Booker_AcceptChecks' => [
                'Caption' => 'Accept Checks',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'AcceptChecks', null),
                'Database' => false,
            ],
            'Booker_ServeEconomyClass' => [
                'Caption' => 'Serve Economy Class',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeEconomyClass', null),
                'Database' => false,
            ],
            'Booker_ServeReservationAir' => [
                'Caption' => 'Reservation: Air',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeReservationAir', null),
                'Database' => false,
            ],
            'Booker_ServeInternational' => [
                'Caption' => 'Air: International',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeInternational', null),
                'Database' => false,
            ],
            'Booker_ServeDomestic' => [
                'Caption' => 'Air: Domestic',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeDomestic', null),
                'Database' => false,
            ],
            'Booker_ServeReservationHotel' => [
                'Caption' => 'Reservation: Hotel',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeReservationHotel', null),
                'Database' => false,
            ],
            'Booker_ServeReservationCar' => [
                'Caption' => 'Reservation: Rental Car',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeReservationCar', null),
                'Database' => false,
            ],
            'Booker_ServeReservationCruises' => [
                'Caption' => 'Reservation: Cruises',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServeReservationCruises', null),
                'Database' => false,
            ],
            'Booker_ServePaymentCash' => [
                'Caption' => 'Payments in Cash',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServePaymentCash', null),
                'Database' => false,
            ],
            'Booker_ServePaymentMiles' => [
                'Caption' => 'Payments in Miles',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServePaymentMiles', null),
                'Database' => false,
            ],
            'Booker_RequirePriorSearches' => [
                'Caption' => 'Require Prior Searches Field',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'RequirePriorSearches', null),
                'Database' => false,
            ],
            'Booker_RequireCustomer' => [
                'Caption' => 'Require Customer Facing',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'RequireCustomer', null),
                'Database' => false,
            ],
            'Booker_DisableAd' => [
                'Caption' => 'Disable Ads',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'DisableAd', null),
                'Database' => false,
            ],
            'Booker_UsCentric' => [
                'Caption' => 'US Centric',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'UsCentric', null),
                'Database' => false,
            ],
            'Booker_ServePremiumEconomy' => [
                'Caption' => 'Serve Premium Economy Class',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'ServePremiumEconomy', null),
                'Database' => false,
            ],
            'Booker_AllowBusinessOrPersonalSelect' => [
                'Caption' => 'Allow business/personal select',
                'Type' => 'boolean',
                'Required' => false,
                'Value' => ArrayVal($this->bookerFields, 'AllowBusinessOrPersonalSelect', null),
                'Database' => false,
            ],
        ]);

        if (isset($this->businessFields) && sizeof($this->businessFields)) {
            ArrayInsert($arFields, 'GroupMembership', true, [
                'Business_Balance' => [
                    'Caption' => 'Business Balance',
                    'Type' => 'float',
                    'Required' => false,
                    'Value' => ArrayVal($this->businessFields, 'Balance', null),
                    'Database' => false,
                ],
                'Business_Discount' => [
                    'Caption' => 'Business Discount',
                    'Type' => 'integer',
                    'Required' => false,
                    'Value' => ArrayVal($this->businessFields, 'Discount', null),
                    'Database' => false,
                ],
                'Business_TrialEndDate' => [
                    'Caption' => 'Business End Trial',
                    'Type' => 'date',
                    'Required' => false,
                    'Value' => empty(ArrayVal($this->businessFields, 'TrialEndDate', null))
                            ? null : date('m/d/Y', strtotime(ArrayVal($this->businessFields, 'TrialEndDate'))),
                    'Database' => false,
                ],
                'Business_APIInviteEnabled' => [
                    'Caption' => 'Allow business to invite users via API invite page',
                    'Type' => 'boolean',
                    'Required' => false,
                    'Value' => ArrayVal($this->businessFields, 'APIInviteEnabled', null),
                    'Database' => false,
                ],
                'Business_APIVersion' => [
                    'Caption' => 'API Version',
                    'Type' => 'integer',
                    'Required' => false,
                    'Value' => ArrayVal($this->businessFields, 'APIVersion', null),
                    'Options' => [
                        1 => 'Old, unsafe authorization',
                        2 => 'New, oauth2-like authorization',
                    ],
                    'Database' => false,
                ],
                'Business_PublicKey' => [
                    'Caption' => 'Business Public Key',
                    'Note' => 'Key for encoding passwords in Account Access API',
                    'Type' => 'string',
                    'InputType' => 'textarea',
                    'Required' => false,
                    'Value' => ArrayVal($this->businessFields, 'PublicKey', null),
                    'Database' => false,
                ],
            ]);
        }

        if (!getSymfonyContainer()->get('security.authorization_checker')->isGranted('ROLE_STAFF_ROOT')) {
            $manager = $arFields['GroupMembership']['Manager'];
            $manager->Checkboxes = SQLToArray("select SiteGroupID, concat('<nobr>', GroupName, ' - ', coalesce(Description, ''), '</nobr>') as GroupName from SiteGroup WHERE GroupName like '%Booking%' or GroupName = 'Do Not Communicate' ORDER BY GroupName", "SiteGroupID", "GroupName");
            $manager->KeepUnknownOptions = true;
        }
        $objManager = new TTableLinksFieldManager();
        $objManager->TableName = "AdBooker";
        $objManager->KeyField = "BookerID";
        $objManager->Fields = [
            "SocialAdID" => [
                "Caption" => "Ads",
                "Type" => "integer",
                "Options" => ["" => ""] + SQLToArray("select SocialAdID, concat(Name, ': ', AdCount) as Name from (select sa.SocialAdID, sa.Name, count(ab.AdBookerID) as AdCount from SocialAd sa left join AdBooker ab on ab.SocialAdID = sa.SocialAdID group by sa.SocialAdID, sa.Name order by count(ab.AdBookerID) desc, sa.Name) a", "SocialAdID", "Name"),
                "Required" => true,
            ],
        ];
        $objManager->UniqueFields = ["SocialAdID"];
        $arFields["BookerAds"] = [
            "Type" => "string",
            "Manager" => $objManager,
        ];

        $Interface->FooterScripts[] = "
		function GroupToggle() {
			if ($('input[name=\"GroupMembership6\"]').is(':checked')) {
				$(\"[id^='trBooker']\").show();
			} else {
				$(\"[id^='trBooker']\").hide();
			}
		}
		GroupToggle();
		$('#trGroupMembership :checkbox').click(GroupToggle);
		";
        unset($arFields["AdvNameNote"]);
        unset($arFields["Amount"]);
        unset($arFields["Understand"]);
        unset($arFields["DisableBrowserExtension"]);

        return $arFields;
    }

    public function CreateList($arFields = null)
    {
        $this->bIncludeList = false;
        $this->ListClass = "TUserAdminList";

        return parent::CreateList($arFields);
    }

    /**
     * @param $list TBaseList
     */
    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->Fields["UserID"]["Sort"] = "u.UserID DESC";
        unset($list->Sorts["ID"]);
        unset($list->DefaultSort2);
        /** @var \Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface $authChecker */
        $authChecker = getSymfonyContainer()->get('security.authorization_checker');
        $list->ReadOnly = !$authChecker->isGranted('ROLE_MANAGE_EDIT_USER');
        $list->AlwaysShowEditLinks = true;
        $list->ShowExport = false;
        $list->ShowImport = false;
        $list->CanAdd = false;
        $list->AllowDeletes = false;
        $list->MultiEdit = false;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnCheck = [&$this, "FormCheck", &$form];
        $form->OnSave = [&$this, "FormSaved", &$form];
    }

    public function FormCheck($objForm)
    {
        if (array_key_exists('GroupMembership', $objForm->Fields) && in_array($this->bookerGroupId, $objForm->Fields['GroupMembership']['Manager']->SelectedOptions)) {
            if (!isset($objForm->Fields['Booker_Price']['Value']) || $objForm->Fields['Booker_Price']['Value'] == '') {
                $objForm->Fields['Booker_Price']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_PricingDetails']['Value']) || $objForm->Fields['Booker_PricingDetails']['Value'] == '') {
                $objForm->Fields['Booker_PricingDetails']['Value'] = null;
            } else {
                $objForm->Fields['Booker_PricingDetails']['Value'] = htmlspecialchars_decode($objForm->Fields['Booker_PricingDetails']['Value']);
            }

            if (!isset($objForm->Fields['Booker_ServiceName']['Value']) || $objForm->Fields['Booker_ServiceName']['Value'] == '') {
                $objForm->Fields['Booker_ServiceName']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ServiceShortName']['Value']) || $objForm->Fields['Booker_ServiceShortName']['Value'] == '') {
                $objForm->Fields['Booker_ServiceShortName']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_Address']['Value']) || $objForm->Fields['Booker_Address']['Value'] == '') {
                $objForm->Fields['Booker_Address']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ServiceURL']['Value']) || $objForm->Fields['Booker_ServiceURL']['Value'] == '') {
                $objForm->Fields['Booker_ServiceURL']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_OutboundPercent']['Value']) || $objForm->Fields['Booker_OutboundPercent']['Value'] == '') {
                $objForm->Fields['Booker_OutboundPercent']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_InboundPercent']['Value']) || $objForm->Fields['Booker_InboundPercent']['Value'] == '') {
                $objForm->Fields['Booker_InboundPercent']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_Discount']['Value']) || $objForm->Fields['Booker_Discount']['Value'] == '') {
                $objForm->Fields['Booker_Discount']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_SmtpServer']['Value']) || $objForm->Fields['Booker_SmtpServer']['Value'] == '') {
                $objForm->Fields['Booker_SmtpServer']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_SmtpPort']['Value']) || $objForm->Fields['Booker_SmtpPort']['Value'] == '') {
                $objForm->Fields['Booker_SmtpPort']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_SmtpUseSsl']['Value']) || $objForm->Fields['Booker_SmtpUseSsl']['Value'] == '') {
                $objForm->Fields['Booker_SmtpUseSsl']['Value'] = 0;
            }

            if (!isset($objForm->Fields['Booker_SmtpUsername']['Value']) || $objForm->Fields['Booker_SmtpUsername']['Value'] == '') {
                $objForm->Fields['Booker_SmtpUsername']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_SmtpPassword']['Value']) || $objForm->Fields['Booker_SmtpPassword']['Value'] == '') {
                $objForm->Fields['Booker_SmtpPassword']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_FromEmail']['Value']) || $objForm->Fields['Booker_FromEmail']['Value'] == '') {
                $objForm->Fields['Booker_FromEmail']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ImapMailbox']['Value']) || $objForm->Fields['Booker_ImapMailbox']['Value'] == '') {
                $objForm->Fields['Booker_ImapMailbox']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ImapLogin']['Value']) || $objForm->Fields['Booker_ImapLogin']['Value'] == '') {
                $objForm->Fields['Booker_ImapLogin']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ImapPassword']['Value']) || $objForm->Fields['Booker_ImapPassword']['Value'] == '') {
                $objForm->Fields['Booker_ImapPassword']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_Greeting']['Value']) || $objForm->Fields['Booker_Greeting']['Value'] == '') {
                $objForm->Fields['Booker_Greeting']['Value'] = null;
            } else {
                $objForm->Fields['Booker_Greeting']['Value'] = htmlspecialchars_decode($objForm->Fields['Booker_Greeting']['Value']);
            }

            if (!isset($objForm->Fields['Booker_AutoReplyMessage']['Value']) || $objForm->Fields['Booker_AutoReplyMessage']['Value'] == '') {
                $objForm->Fields['Booker_AutoReplyMessage']['Value'] = null;
            } else {
                $objForm->Fields['Booker_AutoReplyMessage']['Value'] = htmlspecialchars_decode($objForm->Fields['Booker_AutoReplyMessage']['Value']);
            }

            if (!isset($objForm->Fields['Booker_SiteAdID']['Value']) || $objForm->Fields['Booker_SiteAdID']['Value'] == '') {
                $objForm->Fields['Booker_SiteAdID']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_SmtpPassword']['Value']) || $objForm->Fields['Booker_SmtpPassword']['Value'] == '') {
                $objForm->Fields['Booker_SmtpPassword']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_ServeEconomyClass']['Value']) || $objForm->Fields['Booker_ServeEconomyClass']['Value'] == '') {
                $objForm->Fields['Booker_ServeEconomyClass']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeInternational']['Value']) || $objForm->Fields['Booker_ServeInternational']['Value'] == '') {
                $objForm->Fields['Booker_ServeInternational']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeDomestic']['Value']) || $objForm->Fields['Booker_ServeDomestic']['Value'] == '') {
                $objForm->Fields['Booker_ServeDomestic']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeReservationAir']['Value']) || $objForm->Fields['Booker_ServeReservationAir']['Value'] == '') {
                $objForm->Fields['Booker_ServeReservationAir']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeReservationHotel']['Value']) || $objForm->Fields['Booker_ServeReservationHotel']['Value'] == '') {
                $objForm->Fields['Booker_ServeReservationHotel']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeReservationCar']['Value']) || $objForm->Fields['Booker_ServeReservationCar']['Value'] == '') {
                $objForm->Fields['Booker_ServeReservationCar']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServeReservationCruises']['Value']) || $objForm->Fields['Booker_ServeReservationCruises']['Value'] == '') {
                $objForm->Fields['Booker_ServeReservationCruises']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServePaymentCash']['Value']) || $objForm->Fields['Booker_ServePaymentCash']['Value'] == '') {
                $objForm->Fields['Booker_ServePaymentCash']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_ServePaymentMiles']['Value']) || $objForm->Fields['Booker_ServePaymentMiles']['Value'] == '') {
                $objForm->Fields['Booker_ServePaymentMiles']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_RequirePriorSearches']['Value']) || $objForm->Fields['Booker_RequirePriorSearches']['Value'] == '') {
                $objForm->Fields['Booker_RequirePriorSearches']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_RequireCustomer']['Value']) || $objForm->Fields['Booker_RequireCustomer']['Value'] == '') {
                $objForm->Fields['Booker_RequireCustomer']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_DisableAd']['Value']) || $objForm->Fields['Booker_DisableAd']['Value'] == '') {
                $objForm->Fields['Booker_DisableAd']['Value'] = false;
            }

            if (!isset($objForm->Fields['Booker_CurrencyID']['Value']) || $objForm->Fields['Booker_CurrencyID']['Value'] == '' || $objForm->Fields['Booker_CurrencyID']['Value'] == 0) {
                $objForm->Fields['Booker_CurrencyID']['Value'] = 3;
            }

            if (!isset($objForm->Fields['Booker_PayPalPassword']['Value']) || $objForm->Fields['Booker_PayPalPassword']['Value'] == '') {
                $objForm->Fields['Booker_PayPalPassword']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_PayPalClientId']['Value']) || $objForm->Fields['Booker_PayPalClientId']['Value'] == '') {
                $objForm->Fields['Booker_PayPalClientId']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_PayPalSecret']['Value']) || $objForm->Fields['Booker_PayPalSecret']['Value'] == '') {
                $objForm->Fields['Booker_PayPalSecret']['Value'] = null;
            }

            if (!isset($objForm->Fields['Booker_AcceptChecks']['Value']) || $objForm->Fields['Booker_AcceptChecks']['Value'] == '') {
                $objForm->Fields['Booker_AcceptChecks']['Value'] = 0;
            }

            if (!isset($objForm->Fields['Booker_IncludeCreditCardFee']['Value']) || $objForm->Fields['Booker_IncludeCreditCardFee']['Value'] == '') {
                $objForm->Fields['Booker_IncludeCreditCardFee']['Value'] = 0;
            }

            if ((!isset($objForm->Fields['DefaultBookerID']['Value']) || $objForm->Fields['DefaultBookerID']['Value'] == '') && !empty($objForm->Fields['OwnedByBusinessID']['Value'])) {
                $objForm->Fields['DefaultBookerID']['Value'] = $objForm->Fields['OwnedByBusinessID']['Value'];
            }
        }

        if (isset($this->businessFields) && sizeof($this->businessFields)) {
            if (!isset($objForm->Fields['Business_Balance']['Value']) || $objForm->Fields['Business_Balance']['Value'] == '') {
                $objForm->Fields['Business_Balance']['Value'] = null;
            }

            if (!isset($objForm->Fields['Business_Discount']['Value']) || $objForm->Fields['Business_Discount']['Value'] == '') {
                $objForm->Fields['Business_Discount']['Value'] = null;
            }

            if (!isset($objForm->Fields['Business_TrialEndDate']['Value']) || $objForm->Fields['Business_TrialEndDate']['Value'] == '') {
                $objForm->Fields['Business_TrialEndDate']['Value'] = null;
            }

            if (!isset($objForm->Fields['Business_APIInviteEnabled']['Value']) || $objForm->Fields['Business_APIInviteEnabled']['Value'] == '') {
                $objForm->Fields['Business_APIInviteEnabled']['Value'] = 0;
            }
        }
    }

    public function FormSaved($objForm)
    {
        global $Connection;
        $oldValues = [];
        $fullForm = array_key_exists('GroupMembership', $objForm->Fields);

        if ($fullForm) {
            if (isset($objForm->Fields['GroupMembership']['Manager']->Field['OldValue'])) {
                $oldValues = explode(',', $objForm->Fields['GroupMembership']['Manager']->Field['OldValue']);
            }
            $newValues = $objForm->Fields['GroupMembership']['Manager']->SelectedOptions;

            // Create Booker
            $bookFields = [];

            foreach ($objForm->Fields as $fieldName => $field) {
                if (preg_match("/^Booker_([a-z0-9]+)$/ims", $fieldName, $matches) && $fieldName != 'Booker_hr1') {
                    $val = &$field['Value'];

                    if (preg_match('/^PayPal(Password|ClientId|Secret)/ims', $matches[1]) && !is_null($val)) {
                        $val = getSymfonyContainer()->get(PasswordEncryptor::class)->encrypt($val);
                    }
                    $bookFields[$matches[1]] = (!is_null($val)) ? "'" . addslashes($val) . "'" : 'null';
                }
            }

            if (in_array($this->bookerGroupId, $newValues) && !in_array($this->bookerGroupId, $oldValues)) {
                $bookFields = array_merge($bookFields, [
                    'UserID' => $objForm->ID,
                ]);
                $this->addBooker(array_merge(
                    $bookFields,
                    ($creationDateTime = Lookup('Usr', 'UserID', 'CreationDateTime', $objForm->ID)) ?
                        ['UpdateDate' => "'{$creationDateTime}'"] :
                        []
                ));
            // Update Booker
            } elseif (in_array($this->bookerGroupId, $newValues) && in_array($this->bookerGroupId, $oldValues)) {
                $this->updateBookerFields($objForm->ID, $bookFields);
            // Delete Booker
            } elseif (!in_array($this->bookerGroupId, $newValues) && in_array($this->bookerGroupId, $oldValues)) {
                $this->deleteBooker($objForm->ID);
            }

            if (isset($this->businessFields) && sizeof($this->businessFields)) {
                $businessFields = [];

                foreach ($objForm->Fields as $fieldName => $field) {
                    if (preg_match("/^Business_([a-z0-9]+)$/ims", $fieldName, $matches)) {
                        $val = &$field['Value'];

                        if (in_array($matches[1], ['TrialEndDate']) && !is_null($val)) {
                            $val = date("Y-m-d H:i:s", strtotime($val));
                        }
                        $businessFields[$matches[1]] = (!is_null($val)) ? "'" . addslashes($val) . "'" : 'null';
                    }
                }

                if (sizeof($businessFields)) {
                    $this->updateBusinessFields($objForm->ID, $businessFields);
                }
            }
            /*if (isset($objForm->Fields["Kind"]["OldValue"]) && $objForm->Fields["Kind"]["OldValue"] == ADKIND_EMAIL && $objForm->Fields["Kind"]["Value"] != ADKIND_EMAIL) {
                $Connection->Execute("DELETE FROM AdTypeMail WHERE SocialAdID = ".$objForm->ID);
            }*/
        }
    }

    private function addBooker($fields)
    {
        global $Connection;
        $Connection->Execute(InsertSQL('AbBookerInfo', $fields));
    }

    private function getBookerFields($userID)
    {
        $q = new TQuery("SELECT * FROM AbBookerInfo WHERE UserID = $userID");

        if (!$q->EOF) {
            $q->Fields['PayPalPassword'] = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($q->Fields['PayPalPassword']);
            $q->Fields['PayPalClientId'] = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($q->Fields['PayPalClientId']);
            $q->Fields['PayPalSecret'] = getSymfonyContainer()->get(\AwardWallet\Common\PasswordCrypt\PasswordDecryptor::class)->decrypt($q->Fields['PayPalSecret']);
        }

        if (!$q->EOF) {
            return $this->bookerFields = $q->Fields;
        }

        return $q->Fields;
    }

    private function updateBookerFields($userID, $fields)
    {
        global $Connection;
        $Connection->Execute(UpdateSQL('AbBookerInfo', ['UserID' => $userID], array_merge($fields, ['UpdateDate' => 'NOW()'])));
    }

    private function deleteBooker($userID)
    {
        global $Connection;
        $Connection->Execute(DeleteSQL('AbBookerInfo', ['UserID' => $userID]));
    }

    private function getBusinessFields($userID)
    {
        $q = new TQuery("SELECT * FROM BusinessInfo WHERE UserID = $userID");

        if (!$q->EOF) {
            return $this->businessFields = $q->Fields;
        }
    }

    private function updateBusinessFields($userID, $fields)
    {
        global $Connection;
        $Connection->Execute(UpdateSQL('BusinessInfo', ['UserID' => $userID], $fields));
    }

    private function getGroupId($groupName)
    {
        $q = new TQuery("SELECT SiteGroupID FROM SiteGroup WHERE GroupName = '" . addslashes($groupName) . "'");

        if ($q->EOF) {
            return null;
        }

        return $this->bookerGroupId = $q->Fields['SiteGroupID'];
    }
}
