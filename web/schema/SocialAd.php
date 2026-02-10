<?php

require_once __DIR__ . "/../lib/classes/TBaseSchema.php";

use AwardWallet\MainBundle\Entity\Socialad;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template;

class TSocialAdSchema extends TBaseSchema
{
    public function __construct()
    {
        global $arProviderKind;
        parent::TBaseSchema();
        $this->TableName = "SocialAd";
        $this->Fields = [
            "Kind" => [
                "Type" => "integer",
                "Required" => true,
                "Options" => [
                    "" => "",
                    ADKIND_EMAIL => "Email",
                    ADKIND_BALANCE_CHECK => "Balance check",
                ],
                "Note" => "Influences the way of ad showing",
            ],
            "Name" => [
                "Type" => "string",
                "Size" => 80,
                "Required" => true,
                "InputAttributes" => "style=\"width: 800px;\"",
                "RegExp" => NO_RUSSIAN_REGEXP,
                "RegExpErrorMessage" => 'Possible russian letters in this field',
                "Note" => "Name of advertisement, for staff needs",
            ],
            "Content" => [
                "Type" => "string",
                "Size" => 4000,
                "Required" => false,
                "HTML" => true,
                "RequiredGroup" => "content",
                "InputType" => "htmleditor",
                "RegExp" => NO_RUSSIAN_REGEXP,
                "RegExpErrorMessage" => 'Possible russian letters in this field',
                "Note" => "Text of advertising",
            ],
            "BeginDate" => [
                "Type" => "date",
                "Required" => false,
                "Note" => "Ad display start date, empty date allowed",
            ],
            "EndDate" => [
                "Type" => "date",
                "Required" => false,
                "Note" => "Ad display end date, empty date allowed",
            ],
            "AllProviders" => [
                "Type" => "boolean",
                "Required" => true,
                "Value" => 0,
                "Note" => "Show this ad for all the loyalty program providers",
            ],
            "ProviderKind" => [
                "Type" => "integer",
                "InputType" => "select",
                "filterWidth" => 50,
                "Required" => false,
                "Options" => ["" => ""] + $arProviderKind,
                "Note" => "Show this ad for one or several kinds of loyalty programs",
            ],
            "AdStatus" => [
                "Type" => "string",
                "Options" => ["0" => "Not active", "1" => "Active"],
                "Size" => 40,
                "Sort" => 'AdStatus',
                "FilterType" => 'having',
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        unset($list->Fields['AllProviders']);
        unset($list->Fields['ProviderKind']);

        $arSQLFields = [];

        foreach ($list->Fields as $sField => $arField) {
            if (ArrayVal($arField, "Database", true) && $sField != "AdStatus") {
                $arSQLFields[] = $sField;
            }
        }
        $list->SQL = "
        SELECT
            " . implode(", ", $arSQLFields) . ",
            IF(
                (NOW() >= BeginDate AND NOW() <= EndDate OR NOW() >= BeginDate AND EndDate IS NULL) AND
                (AllProviders = 1 OR ProviderKind IS NOT NULL OR ap.AdProviderID IS NOT NULL)
                , 1, 0) AS AdStatus
        FROM
            SocialAd sa
            LEFT OUTER JOIN (SELECT SocialAdID AS sid, AdProviderID FROM AdProvider) ap ON ap.sid = sa.SocialAdID
        WHERE
			1 = 1
			AND sa.Kind in (" . implode(" ,", [ADKIND_BALANCE_CHECK, ADKIND_EMAIL]) . ")
			[Filters]
        GROUP BY " . implode(", ", $arSQLFields) . ", AdStatus
        ";
    }

    public function GetFormFields()
    {
        global $Interface;
        $arFields = parent::GetFormFields();

        $adId = intval(ArrayVal($_GET, "ID", 0));
        $ad = null;

        if (!empty($adId)) {
            /** @var Socialad $ad */
            $ad = $this->getAd($adId);
        }

        if (!$ad) {
            $arFields['AllProviders']['Value'] = 1;
        }

        $objManager = new TTableLinksFieldManager();
        $objManager->TableName = "AdProvider";
        $objManager->Fields = [
            "ProviderID" => [
                "Caption" => "Providers",
                "Type" => "integer",
                "Options" => ["" => ""] + SQLToArray("select ProviderID, DisplayName from Provider where State = 1 order by DisplayName", "ProviderID", "DisplayName"),
                "Required" => true,
            ],
        ];
        $objManager->UniqueFields = ["ProviderID"];
        $arFields["Providers"] = [
            "Type" => "string",
            "Manager" => $objManager,
            "Note" => "Show this ad for one or several specific loyalty programs",
        ];
        $objManager = new TTableLinksFieldManager();
        $objManager->TableName = "AdBooker";
        $objManager->Fields = [
            "BookerID" => [
                "Caption" => "Bookers",
                "Type" => "integer",
                "Options" => ["" => ""] + SQLToArray("select UserID as BookerID, ServiceName from AbBookerInfo order by ServiceName", "BookerID", "ServiceName"),
                "Required" => true,
            ],
        ];
        $objManager->UniqueFields = ["BookerID"];
        $arFields["Bookers"] = [
            "Type" => "string",
            "Manager" => $objManager,
            "Note" => "Show this ad to clients of a specific booker",
        ];

        if ($ad) {
            $q = new TQuery("
				select 
					SUM(Messages) as Messages, 
					SUM(Clicks) as Clicks,
					SUM(Sent) as Sent
			  	from AdStat where SocialAdID = " . intval($_GET['ID']));

            if ($ad->getKind() == ADKIND_EMAIL) {
                $arFields["SentCount"] = [
                    "Type" => "html",
                    "Caption" => "Sent Count",
                    "HTML" => "<p>{$q->Fields['Sent']}</p>",
                    "Note" => "Total number of ads sent",
                ];
            }
            $arFields["ShowsCount"] = [
                "Type" => "html",
                "Caption" => "Shows Count",
                "HTML" => "<p> {$q->Fields['Messages']}</p>",
                "Note" => "Total number of ads shown on the website or in emails",
            ];
            $arFields["ClicksCount"] = [
                "Type" => "html",
                "Caption" => "Clicks Count",
                "HTML" => "<p> {$q->Fields['Clicks']}</p>",
                "Note" => "Total number of clicks on links from this ad",
            ];
        }
        $arFields['InternalNote'] = [
            "Type" => "string",
            "Caption" => "Internal Note",
            "InputType" => "textarea",
            "Size" => 4000,
            "Value" => '',
            "Required" => false,
            "Note" => "For staff needs",
        ];
        ArrayInsert($arFields, "Content", true, ["Preview" => [
            "Type" => "string",
            "InputType" => "html",
            "Database" => false,
            "HTML" => $this->getPreviewHTML($adId),
        ]]);
        $objManager = new TTableLinksFieldManager();
        $objManager->TableName = "AdTypeMail";
        $objManager->Fields = [
            "TypeMail" => [
                "Caption" => "Email type", /* checked */
                "Type" => "string",
                "Options" => [
                    Template\Itinerary\ReservationNew::getEmailKind() => 'New Travel Reservation',
                    Template\Account\RewardsActivity::getEmailKind() => 'Rewards Activity',
                    Template\Account\BalanceExpiration::getEmailKind() => 'Award Program Point Expiration Notice',
                    Template\Itinerary\CheckIn::getEmailKind() => 'Online Check-In Reminder',
                    Template\Itinerary\ReservationChanged::getEmailKind() => 'Travel plan updates',
                ],
                "Required" => true,
                "Note" => "<style>#trTypeMail {display: none}</style>",
            ],
        ];
        $objManager->UniqueFields = ["TypeMail"];
        ArrayInsert($arFields, "Kind", true, ["TypeMail" => [
            "Type" => "string",
            "Manager" => $objManager,
            "Note" => "Email types in which this ad will be shown",
        ]]);

        $allGeoGroups = [];

        foreach (Socialad::GEO_GROUPS as $group => $label) {
            $allGeoGroups[] = "
				<input type='checkbox' 
					id='gt_" . $group . "' 
					name='GeoTargeting[]' 
					value='" . $group . "'
					" . (
                (
                    (!$ad && $group === Socialad::GEO_GROUP_ALL)
                    || ($ad && $ad->hasGeoGroup($group))
                ) ? 'checked="checked"' : ''
            ) . "
				> <label for='gt_" . $group . "'>" . $label . "</label><br>";
        }
        ArrayInsert($arFields, "Kind", true, ["GeoTargeting" => [
            "Type" => "string",
            "InputType" => "html",
            "Database" => false,
            "HTML" => implode("", $allGeoGroups),
        ]]);

        $Interface->FooterScripts[] = "
			function mailTypeToggle() {
				var selected;
				selected = $(\"select#fldKind option:selected\").val();
				if (selected == '" . ADKIND_EMAIL . "') {
					$(\"#trTypeMail\").show();
					$(\"#emailPreview\").show();
					$(\"#webPreview\").hide();
					$(\"label[for=fldPreview]\").text(\"Test Email\");
				} else {
					$(\"#trTypeMail\").hide();
					$(\"#emailPreview\").hide();
					$(\"#webPreview\").show();
					$(\"label[for=fldPreview]\").text(\"Preview\");
				}
			}
			mailTypeToggle();
			$('select#fldKind').change(mailTypeToggle);
		";
        unset($arFields['AdStatus']);

        return $arFields;
    }

    public function TuneForm(TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->Uniques = [
            [
                "Fields" => ["Name"],
                "ErrorMessage" => "This name already exists. Please choose another name.",
            ],
        ];
        $form->OnSave = [&$this, "FormSaved", &$form];
    }

    public function FormSaved($objForm)
    {
        global $Connection;

        if (isset($objForm->Fields["Kind"]["OldValue"]) && $objForm->Fields["Kind"]["OldValue"] == ADKIND_EMAIL && $objForm->Fields["Kind"]["Value"] != ADKIND_EMAIL) {
            $Connection->Execute("DELETE FROM AdTypeMail WHERE SocialAdID = " . $objForm->ID);
        }
        $geoGroups = ArrayVal($_POST, "GeoTargeting", []);
        $ad = $this->getAd($objForm->ID);
        $ad->setGeoGroups(Socialad::GEO_GROUP_ALL);

        if (is_array($geoGroups) && !empty($geoGroups)) {
            $geoGroups = array_map("intval", $geoGroups);

            if (!in_array(Socialad::GEO_GROUP_ALL, $geoGroups, true)) {
                foreach ($geoGroups as $group) {
                    $ad->addGeoGroup($group);
                }
            }
        }
        getSymfonyContainer()->get("doctrine.orm.default_entity_manager")->flush();
    }

    /**
     * @param int $id
     * @return Socialad
     */
    public function getAd($id)
    {
        return getSymfonyContainer()->get("doctrine.orm.default_entity_manager")
            ->getRepository(\AwardWallet\MainBundle\Entity\Socialad::class)->find($id);
    }

    public function getPreviewHTML($adId)
    {
        $defaultEmail = $_SESSION['Email'] ?? "";
        $options = [
            Template\Itinerary\ReservationNew::getEmailKind() => 'New Travel Reservation',
            Template\Account\RewardsActivity::getEmailKind() => 'Rewards Activity',
            Template\Account\BalanceExpiration::getEmailKind() => 'Award Program Point Expiration Notice',
            Template\Itinerary\CheckIn::getEmailKind() => 'Online Check-In Reminder',
            Template\Itinerary\ReservationChanged::getEmailKind() => 'Travel plan updates',
        ];
        $opts = "";

        foreach ($options as $k => $option) {
            $opts .= '<option value="' . $k . '">' . $option . '</option>';
        }

        return <<<HTML
			
			<input id='webPreview' type='button' value='Show Preview' onclick='showPreview(); return false;'>
			
			<table id='emailPreview' width="100%" cellpadding="5" cellspacing="0" border="0">
				<tr>
					<td width="1">
						<input type='email' name='prevewEmail' value='$defaultEmail' style="padding: 5px; margin-bottom: 10px; width: 250px"/><br>
						<select name="previewEmailTemplate" style="width: 260px"">$opts</select>			
					</td>			
					<td style="text-align: left">
						<input type='button' value='Send Test Email' onclick='sendTestEmail(); return false;'>
						<span id="emailPreview-flash-message" style="display: none; color: white; background-color: green; padding: 10px; font-size: 13px">The email was sent successfully</span>	
					</td>			
				</tr>
			</table>
			
			<script>
			function showPreview(){
				var form = document.forms['editor_form'];
                form.action = 'http://{$_SERVER['HTTP_HOST']}/advertise/$adId';
				form.target = '_blank';
				form.submit();
				setTimeout(function(){
					form.action = '';
					form.target = '';
					submitonce(form, true);
				}, 1000);
			}
			
			function sendTestEmail(){
			    $.post("/manager/emailViewer/sendAdvt", {
			        "template": $("select[name=previewEmailTemplate]").val(), 
			        "advt": CKEDITOR.instances.Content.getData(), 
			        "email": $("#emailPreview [name=prevewEmail]").val()
				}, function(data) {
					if (typeof(data.success) != 'undefined') {
						$('#emailPreview-flash-message').slideDown("slow", function() {
							setTimeout(function() {
								$('#emailPreview-flash-message').hide();
							}, 5000);
						});
					} else if (typeof(data.error) != 'undefined') {
					    alert(data.error);
					}
				});
			}
			</script>
HTML;
    }
}
