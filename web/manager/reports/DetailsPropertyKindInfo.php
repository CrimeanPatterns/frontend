<?php

$schema = "providerDetailsPropsInfo";

require "../start.php";

class DetailPropertyKindList extends TBaseList
{
    public $isPrivateProviders = false;

    protected $privateProviders = [
        7,   // Delta SkyMiles
        26,  // United MileagePlus
        22,  // Hilton HHonors
        17,  // Marriott Rewards
        25,  // Starwood Preferred Guest
        21,  // Hertz #1 Club
        27,  // US Airways Dividend Miles
        12,  // Priority Club Rewards
        10,  // Hyatt Gold Passport
        13,  // JetBlue Airways TrueBlue
        31,  // British Airways Executive Club
        72,  // Virgin America Elevate
        84,  // Amex
        18,  // Alaska Mileage Plan
        3,   // AirTran A+ Rewards
        33,  // Qantas Frequent Flyer
        28,  // Amtrak Guest Rewards
        39,  // Lufthansa Miles & More (Rest of World)
        9,   // Frontier EarlyReturns
        383, // Club Carlson
        2,   // Air Canada Aeroplan
        40,  // Virgin Atlantic Flying Club
        48,  // Emirates Skywards
        19,  // Best Western Rewards
        88,  // Accor Hotels A|Club
    ];

    public function __construct()
    {
        global $arProviderState, $arProviderKind, $arDeepLinking;
        parent::__construct("Provider", [
            "Number" => [
                "Caption" => "#",
                "Type" => "string",
                "database" => false,
                "NoFilter" => true,
            ],
            "Code" => [
                'Caption' => "Provider Code",
                'Type' => "string",
            ],
            "DisplayName" => [
                "Type" => "string",
            ],
            "Properties" => [
                "Type" => "string",
                'database' => false,
            ],
            "SampleData" => [
                "Type" => "string",
                'database' => false,
            ],
            "StandardProps" => [
                "Caption" => "Standard Properties",
                "Type" => "string",
                'database' => false,
            ],
            "State" => [
                "Type" => "integer",
                "Options" => [14 => "for WSDL clients"] + $arProviderState,
            ],
            "Corporate" => [
                "Type" => "integer",
                "Options" => [
                    0 => "no",
                    1 => "yes",
                ],
            ],
            "Kind" => [
                "Type" => "integer",
                "Options" => $arProviderKind,
            ],
            "CanCheckItinerary" => [
                "Type" => "boolean",
                "Caption" => "Travel itineraries",
            ],
            "CanCheckHistory" => [
                "Type" => "boolean",
                "Caption" => "History",
            ],
            "AutoLogin" => [
                "Type" => "boolean",
                "Caption" => "Auto-login",
            ],
            "DeepLinking" => [
                "Type" => "integer",
                "Options" => $arDeepLinking,
            ],
            "WSDL" => [
                "Type" => "integer",
                "Options" => [0 => 'No', 1 => 'Yes'],
            ],
            "Accounts" => [
                "Type" => "integer",
                "Sort" => "Accounts DESC",
                "Caption" => 'Popularity ranking',
            ],
        ], "DisplayName");

        if (isset($_GET['privateProvider']) || isset($_GET['privateProviders'])) {
            $this->isPrivateProviders = true;
        }
        $where = [];

        if ($this->isPrivateProviders) {
            $where[] = "p.ProviderID IN (" . implode(',', $this->privateProviders) . ")";
        }
        $this->SQL = "
        SELECT
            p.ProviderID,
            p.DisplayName,
            p.CanCheckBalance,
            p.CanCheckExpiration,
            p.Code,
            p.BalanceFormat,
            p.State,
            p.Corporate,
            p.Accounts,
            p.Kind,
            p.CanCheckHistory,
            p.CanCheckItinerary,
            p.AutoLogin,
            p.DeepLinking,
            p.WSDL
        FROM
            Provider p
        WHERE
            1 = 1
        " . (!empty($where) ? " AND " . implode(' AND ', $where) : "") . "
        [Filters]
        ";
        $this->ShowFilters = true;
        $this->PageSizes["1000"] = "1000";
        $this->PageSize = 1000;
        $this->ShowExport = true;
        $this->ShowEditors = true;
        $this->ReadOnly = true;
        $this->DefaultSort = 'Accounts';
        // $this->Sorts = array("Accounts");
        // $this->Sort1 = "Accounts";
        // $this->isWhere = true;
    }

    public function FormatFields($output = "html")
    {
        global $arPropertiesKinds;
        $missingProperties = ArrayVal($_GET, 'missingProperties', 0);

        $arPropertiesKinds[0] = '-';
        $arPropertiesKinds[1000] = 'Balance';
        parent::FormatFields($output);
        $arFields = &$this->Query->Fields;
        $props = $this->getPropertiesData($arFields);

        $isSetPropertiesKinds = [];

        foreach ($props as $i => $property) {
            $isSetPropertiesKinds[] = intval($property['Kind']);
        }
        $isSetPropertiesKinds = array_unique($isSetPropertiesKinds);
        $propertiesKindsLocal = $arPropertiesKinds;

        foreach ($isSetPropertiesKinds as $kind) {
            unset($propertiesKindsLocal[$kind]);
        }

        $propNameHtml = "";
        $propKindHtml = "";
        $propValHtml = "";
        $isSetPropertiesKinds = [];
        $style = " style='border-bottom:1px solid #999; padding:2px 0; white-space:nowrap; max-width:290px; overflow:hidden;' ";

        foreach ($props as $i => $property) {
            if ($i + 1 == count($props)) {
                $style = " style='white-space:nowrap; max-width:400px; overflow:hidden; padding:2px 0;' ";
            }
            $propNameHtml .= "<div $style>" . $property['Name'] . "</div>";
            $propKindHtml .= "<div $style>" . $arPropertiesKinds[intval($property['Kind'])] . "</div>";
            $propValHtml .= "<div $style>" . $property['Val'] . "</div>";
        }

        if (!$missingProperties) {
            if (isset($propertiesKindsLocal[0])) {
                unset($propertiesKindsLocal[0]);
            }

            foreach ($propertiesKindsLocal as $emptyProperty) {
                $propNameHtml .= "<div $style>-</div>";
                $propKindHtml .= "<div $style>" . $emptyProperty . "</div>";
                $propValHtml .= "<div $style>-</div>";
            }
        }
        $arFields['Number'] = $this->RowCount + 1;
        $arFields['DisplayName'] = "<b>" . $arFields['DisplayName'] . "</b>";
        $arFields['Properties'] = $propNameHtml;
        $arFields['SampleData'] = $propValHtml;
        $arFields['StandardProps'] = $propKindHtml;

        if (isset($propertiesKindsLocal[0])) {
            unset($propertiesKindsLocal[0]);
        }
        // $arFields['Accounts'] = false;
    }

    public function ExportExcel()
    {
        $sSeparator = ',';
        $this->ExportName = "PropertyKinds";
        header("Content-type: text/xls; charset=utf-8");
        header("Content-Disposition: attachment; filename={$this->ExportName}.xls");

        echo "<table cellspacing='0' cellpadding='3' border='1'>";

        if ($this->ExportCSVHeader) {
            $captions = [];
            $captions[] = "#";
            $captions[] = '<b style="font-size:13px;">Provider Code</b>';
            $captions[] = '<b style="font-size:13px;">Display Name</b>';
            $captions[] = '<b style="font-size:13px;">Properties</b>';
            $captions[] = '<b style="font-size:13px;">Sample Data</b>';
            $captions[] = '<b style="font-size:13px;">Standard Properties</b>';
            $captions[] = '<b style="font-size:13px;">Kind</b>';
            $captions[] = '<b style="font-size:13px;">Travel itineraries</b>';
            $captions[] = '<b style="font-size:13px;">History</b>';
            $captions[] = '<b style="font-size:13px;">Auto-login,</b>';
            $captions[] = '<b style="font-size:13px;">Deep Linking</b>';
            $this->ExportExcelRow($captions, true, true);
        }

        global $arPropertiesKinds, $arProviderKind, $arDeepLinking;
        $arPropertiesKinds[0] = '-';
        $arPropertiesKinds[1000] = 'Balance';
        $this->OpenQuery();
        $q = &$this->Query;
        $i = 0;

        while (!$q->EOF) {
            $arFields = $q->Fields;
            $props = $this->getPropertiesData($arFields);

            $isSetPropertiesKinds = [];

            foreach ($props as $property) {
                $isSetPropertiesKinds[] = intval($property['Kind']);
            }
            $isSetPropertiesKinds = array_unique($isSetPropertiesKinds);
            $propertiesKindsLocal = $arPropertiesKinds;

            foreach ($isSetPropertiesKinds as $kind) {
                unset($propertiesKindsLocal[$kind]);
            }

            $putCsv = [];
            $putCsv[] = "<div style='text-align:center'>" . ($i + 1) . "</div>";
            $putCsv[] = $arFields['Code'];
            $putCsv[] = "<b>" . $arFields['DisplayName'] . "</b>";
            $nameHTML = $valHTML = $kindHTML = "<table border='1' style='border-color:#aaa;'>";
            $j = 0;

            foreach ($props as $property) {
                $bgColor = "";

                if ($j % 2 != 0) {
                    if ($i % 2 != 0) {
                        $bgColor = "bgcolor='#F5F2EB'";
                    } else {
                        $bgColor = "bgcolor='#FFFCF5'";
                    }
                }

                $nameHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa;'>" . $property['Name'] . "</td></tr>";
                $valHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa; text-align:left;'>" . $property['Val'] . "</td></tr>";
                $kindHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa;'>" . $arPropertiesKinds[intval($property['Kind'])] . "</td></tr>";
                $j++;
            }
            $missingProperties = ArrayVal($_GET, 'missingProperties', 0);

            if (isset($propertiesKindsLocal[0])) {
                unset($propertiesKindsLocal[0]);
            }

            if (!$missingProperties) {
                foreach ($propertiesKindsLocal as $emptyProperty) {
                    $bgColor = "";

                    if ($j % 2 != 0) {
                        if ($i % 2 != 0) {
                            $bgColor = "bgcolor='#F5F2EB'";
                        } else {
                            $bgColor = "bgcolor='#FFFCF5'";
                        }
                    }
                    $nameHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa;'>-</td></tr>";
                    $valHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa; text-align:left;'>-</td></tr>";
                    $kindHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa;'>$emptyProperty</td></tr>";
                    $j++;
                }
            }
            $nameHTML .= "</table>";
            $valHTML .= "</table>";
            $kindHTML .= "</table>";
            $putCsv[] = $nameHTML;
            $putCsv[] = $valHTML;
            $putCsv[] = $kindHTML;
            $putCsv[] = $arProviderKind[$arFields['Kind']];
            $putCsv[] = $arFields['CanCheckItinerary'] ? 'yes' : 'no';
            $putCsv[] = $arFields['CanCheckHistory'] ? 'yes' : 'no';
            $putCsv[] = $arFields['AutoLogin'] ? 'yes' : 'no';
            $putCsv[] = $arDeepLinking[$arFields['DeepLinking']];
            $this->ExportExcelRow($putCsv, $i % 2 == 0, false);
            $q->Next();
            $i++;
        }
        echo "</tr></table>";
    }

    public function ExportExcelRow($arValues, $grey = false, $head = false)
    {
        $bgColorRow = "bgcolor='#FFFCF5'";

        if ($grey) {
            $bgColorRow = "bgcolor='#F5F2EB'";
        }

        if ($head) {
            $bgColorRow = "bgcolor='#F63636' style='color:white;'";
        }

        echo "<tr {$bgColorRow}><td style='vertical-align: middle; font-size: 12px;'>";
        $sSeparator = "</td><td style='vertical-align: middle;'>";
        echo implode($sSeparator, $arValues);
        echo "</td></tr>";
    }

    public function DrawFooter()
    {
        parent::DrawFooter();
    }

    public function DrawHeader()
    {
        $getParamsOrigin = $getParams = $_GET;

        $missingProperties = ArrayVal($_GET, 'missingProperties', 0);
        $exportValue = ArrayVal($_GET, 'export', 0);

        if ($exportValue == 0) {
            $getParams['export'] = 1;
        }
        $stringParams = ImplodeAssoc('=', '&', $getParams);
        $stringParams = !empty($stringParams) ? "?" . $stringParams : "";
        echo "<a style='color:red; font-size:13px;' href='" . $stringParams . "'>Export to excel</a>&nbsp;&nbsp;|&nbsp;&nbsp;";

        $getParams = $getParamsOrigin;

        if ($missingProperties == 0) {
            $getParams['missingProperties'] = 1;
            $missingPropCaption = "Without missing properties";
        } else {
            $getParams['missingProperties'] = 0;
            $missingPropCaption = "With missing properties";
        }
        $stringParams = ImplodeAssoc('=', '&', $getParams);
        $stringParams = !empty($stringParams) ? "?" . $stringParams : "";
        echo "<a style='color:red; font-size:13px;' href='" . $stringParams . "'>$missingPropCaption</a>&nbsp;&nbsp;|&nbsp;&nbsp;";
        echo "<br/><br/>";
        parent::DrawHeader();
    }

    public function DrawFieldFilter($sFieldName, &$arField)
    {
        if (isset($this->FilterForm->Fields[$sFieldName]) && !isset($arField['NoFilter'])) {
            echo "<td nowrap";

            if (isset($this->FilterForm->Fields[$sFieldName]["Error"])) {
                echo " class=formErrorCell";
            }
            echo "><table class=noBorder><tr><td>" . $this->FilterForm->InputHTML($sFieldName) . "</td><td><input type=Image name=s1 width=8 height=7 src='/lib/images/button1.gif' style='border: none; margin-bottom: 1px; margin-right: 0px;'></td></tr></table></td>";
        } else {
            echo "<td>&nbsp;</td>\n";
        }
    }

    public function AddFilters($sSQL)
    {
        global $arProviderStateForWsdlClients;
        $sql = parent::AddFilters($sSQL);

        return str_replace('State = 14', 'State IN(' . implode(', ', $arProviderStateForWsdlClients) . ')', $sql);
    }

    protected function getPropertiesData($arFields)
    {
        $sql = "
        SELECT pp.ProviderPropertyID,
            pp.ProviderID,
            pp.Name,
            pp.Kind
        FROM
            ProviderProperty pp
        WHERE
            pp.ProviderID = {$arFields['ProviderID']}
        ORDER BY pp.SortIndex
        ";
        $props = [];
        $q = new TQuery($sql);

        while (!$q->EOF) {
            $sql = "
                SELECT ap.Val
                FROM Account a
                JOIN AccountProperty ap on ap.AccountID = a.AccountID
                JOIN ProviderProperty pp on ap.ProviderPropertyID = pp.ProviderPropertyID
                LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                WHERE pp.ProviderPropertyID = {$q->Fields['ProviderPropertyID']}
                    AND a.UpdateDate >= adddate(now(), -7)
                    AND a.ErrorCode = 1
            ";
            $qProp = new TQuery($sql . " LIMIT 1");

            if (!$qProp->EOF) {
                $q->Fields['Val'] = $this->replaceSecurityProperty($qProp->Fields['Val'], $q->Fields['Kind'], $q->Fields['Name']);
                $this->filterProperties($q->Fields, $sql);
                $props[] = $q->Fields;
            }

            if ($q->Fields['Kind'] == PROPERTY_KIND_STATUS && isset($q->Fields['Val'])) {
                $nextEliteLevel = getRepository(\AwardWallet\MainBundle\Entity\Elitelevel::class)
                    ->nextEliteLevel($arFields['ProviderID'], $q->Fields['Val']);

                if (isset($nextEliteLevel)) {
                    if ($nextEliteLevel === false) {
                        $nextEliteLevel = $q->Fields['Val'];
                    }
                    $props[] = [
                        'Name' => 'Next Elite Level',
                        'Kind' => PROPERTY_KIND_NEXT_ELITE_LEVEL,
                        'Val' => $nextEliteLevel,
                        'ProviderPropertyID' => null,
                    ];
                }
            }
            $q->Next();
        }

        $sql = "
            SELECT a.Balance,
                a.ExpirationDate
            FROM
                Account a
            WHERE a.ProviderID = {$arFields['ProviderID']}
                AND a.Balance IS NOT NULL
                " . ($arFields['CanCheckBalance'] == 1 ? " AND a.Balance <> 0" : " ") . "
                " . ($arFields['CanCheckExpiration'] == 1 ? " AND a.ExpirationDate IS NOT NULL" : "") . "
            LIMIT 1;
        ";
        $qBalance = new TQuery($sql);

        if (!$qBalance->EOF) {
            $expDate = false;

            switch ($arFields['CanCheckExpiration']) {
                case CAN_CHECK_EXPIRATION_YES:
                    $expDate = date("m/d/Y", strtotime($qBalance->Fields['ExpirationDate']));

                    break;

                case CAN_CHECK_EXPIRATION_NEVER_EXPIRES:
                    $expDate = 'Never Expires';

                    break;
            }

            if ($expDate) {
                array_unshift($props, [
                    'Name' => 'Expiration Date',
                    'Kind' => PROPERTY_KIND_EXPIRATION,
                    'Val' => $expDate,
                    'ProviderPropertyID' => null,
                ]
                );
            }

            if ($arFields['CanCheckBalance']) {
                array_unshift($props, [
                    'Name' => 'Balance',
                    'Kind' => 1000,
                    'Val' => formatFullBalance($qBalance->Fields['Balance'], $arFields['Code'], $arFields['BalanceFormat']),
                    'ProviderPropertyID' => null,
                ]
                );
            }
        }
        // if()

        return $props;
    }

    protected function replaceSecurityProperty($value, $kind, $name)
    {
        if ($kind == PROPERTY_KIND_NAME || $name == 'Name' || $name == 'Member Name' || $name == 'Name In Chinese') {
            return "John Smith";
        }

        if ($kind == PROPERTY_KIND_NUMBER || $name == 'Number' || $name == 'Account number' || preg_match('/Account \d+/ims', $name)) {
            return strtoupper(substr(md5($value), 0, strlen($value)));
        }

        return $value;
    }

    protected function filterProperties(&$fields, $sql, &$addSql = "", $oldValue = null, $num = 0)
    {
        $filters = [];

        if (isset($fields['filters'])) {
            $filters = $fields['filters'];
        }
        $this->addFilterProp($fields['Val'], $addSql, $filters, $oldValue, $num);

        if ($addSql != "") {
            $fields['filters'] = $filters;
            $q = new TQuery($sql . $addSql . " LIMIT 1");

            if (!$q->EOF) {
                $fields['Val'] = $q->Fields['Val'];
            }
            $this->filterProperties($fields, $sql, $addSql, $oldValue, $num);
        }
    }

    protected function addFilterProp($value, &$addSql, &$filters, &$oldValue, &$num)
    {
        switch ($value) {
            case '-':
            case '--':
            case '---':
            case '-1':
            case 'n/a':
            case '0':
                if (!in_array($value, $filters)) {
                    if ($value == '-' || $value == '--') {
                        $addSql .= " AND ap.Val != '-' AND ap.Val != '--' AND ap.Val != '---' ";
                    } else {
                        $addSql .= " AND ap.Val != '{$value}' ";
                    }
                    $filters[] = $value;
                    $oldValue = $value;
                } elseif ($value == $oldValue) {
                    $num++;

                    if ($num == 6) {
                        $addSql = "";
                        $num = 0;
                    }
                }

                break;

            default:
                $addSql = "";
                $num = 0;
        }
    }
}

if (!isset($_GET['WSDL']) || !isset($_GET['missingProperties'])) {
    Redirect("/manager/reports/DetailsPropertyKindInfo.php?State=1&Corporate=0&WSDL=1&missingProperties=1");
}
$list = new DetailPropertyKindList();

if (isset($_GET['export'])) {
    $list->ExportCSVHeader = true;
    $list->ExportExcel();
} else {
    drawHeader("Loyalty Properties Schedule");
    $list->Draw();
    drawFooter();
}
