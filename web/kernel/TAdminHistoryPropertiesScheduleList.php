<?php

use AwardWallet\MainBundle\Globals\StringUtils;
use Doctrine\DBAL\TransactionIsolationLevel;

class TAdminHistoryPropertiesScheduleList extends TBaseList{

    // generate random data
    const RANDOM_NUMBER = 1;
    const RANDOM_NAME = 2;

    private $randomNames = ['John Smith', 'Kate Smith', 'John Smith, Kate Smith'];

    // itinerary updated or created ITINERARY_AGE days ago
    const HISTORY_AGE = 7;

	private $tier2;
	private $tier3;

    /**
     * @var string[]
     */
	private $currenciesData;

    // hides from output
    private $privateAttributes = [
        'Booked by' => self::RANDOM_NAME,
        'Redeemed By' => self::RANDOM_NAME,
        'Name' => self::RANDOM_NAME,
        'Relationship' => self::RANDOM_NAME,
        'Rental Agreement Number' => self::RANDOM_NUMBER,
        'Rewards' => self::RANDOM_NUMBER,
        'Confirmation' => self::RANDOM_NUMBER,
        'Folio #' => self::RANDOM_NUMBER,
        'SKYPASS No' => self::RANDOM_NUMBER,
        'Rental Agreement #' => self::RANDOM_NUMBER,
        'RENTAL #' => self::RANDOM_NUMBER,
        'Voucher Number' => self::RANDOM_NUMBER,
        'Reference' => self::RANDOM_NUMBER,
    ];

	function __construct(){
		global $arDeepLinking, $arProviderState, $arDetailTable, $arProviderKind;
		parent::__construct("Provider", array(
			"Code" => array(
				"Type" => "string",
				"filterWidth" => 40,
			),
			"DisplayName" => array(
				"Type" => "string",
			),
			"Kind" => array(
				"Type" => "integer",
				"Options" => $arProviderKind
			),
            "CanCheckHistory" => array(
                "Type" => "boolean",
                "Caption" => "History properties",
            ),
			"Popularity" => array(
				"Type" => "integer",
				"Caption" => "Popularity Ranking",
				"Sort" => "Popularity DESC",
			),
			"Attributes" => array(
				"Type" => "string",
				"Database" => false,
				"Caption" => "Properties",
			),
            "SampleData" => array(
                "Type" => "string",
                "Database" => false,
                "Caption" => "Sample data",
            ),
			"Tier" => array(
				"Type" => "string",
				"Database" => false,
			),
            "WSDL" => array(
				"Type" => "boolean",
			),
            "Corporate" => array(
				"Type" => "boolean",
			),
            "State" => array(
				"Type" => "integer",
				"Options" => array(14 => "for WSDL clients") + $arProviderState,
			),
		), "Popularity");
		$this->SQL = "select
			p.ProviderID,
			p.Code,
			p.DisplayName,
			p.Kind,
			p.CanCheckHistory,
			p.AutoLogin,
			p.Accounts as Popularity,
            p.WSDL,
            p.Corporate,
            p.State
		FROM
			Provider p
        ";
		$this->ShowFilters = true;
        $this->TopButtons = true;
        $this->ShowExport = false;
        $this->AllowDeletes = false;
        $this->ReadOnly = true;
        $this->MultiEdit = false;
		$this->calcTiers();
		$this->loadCurrencies();
		$this->PageSizes["5000"] = "5000";
		$this->PageSize = 5000;
		$this->ExportName = "HistoryProperties";
	}

    function ExportExcelHeaders()
    {
        $this->ExportName = "HistoryProperties";
        header("Content-type: text/xls; charset=utf-8");
        header("Content-Disposition: attachment; filename={$this->ExportName}.xls");
    }

    function ExportExcel()
    {
        $this->ExportExcelHeaders();
        $this->DoExportExcel();
    }

    function DoExportExcel()
    {
        global $arProviderKind, $arDeepLinking;

        $this->ExportName = "HistoryProperties";

        echo "<table cellspacing='0' cellpadding='3' border='1'>";
        if($this->ExportCSVHeader){
            $captions = array();
            $captions[] = "#";
            $captions[] = '<b style="font-size:13px;">Provider Code</b>';
            $captions[] = '<b style="font-size:13px;">Display Name</b>';
            $captions[] = '<b style="font-size:13px;">Can Check History</b>';
            $captions[] = '<b style="font-size:13px;">AutoLogin</b>';
            $captions[] = '<b style="font-size:13px;">Kind</b>';
            $captions[] = '<b style="font-size:13px;">Properties</b>';
            $captions[] = '<b style="font-size:13px;">Sample Data</b>';
            $captions[] = '<b style="font-size:13px;">State</b>';
            $this->ExportExcelRow( $captions, true, true );
        }

        $this->OpenQuery();
        $q = &$this->Query;
        $i = 0;
        while(!$q->EOF){
            $arFields = $q->Fields;
            $props = $this->loadHistoryFields($arFields);

            $putCsv = array();
            $putCsv[] = "<div style='text-align:center'>".($i + 1)."</div>";
			$putCsv[] = $arFields['Code'];
            $putCsv[] = "<b>".$arFields['DisplayName']."</b>";
            $nameHTML = $valHTML = $kindHTML = "<table border='1' style='border-color:#aaa;'>";
            $j = 0;
            foreach($props as $propertyName => $propertyValue){
                $bgColor = "";
                if($j%2 != 0){
                    if($i%2 != 0)
                        $bgColor = "bgcolor='#F5F2EB'";
                    else
                        $bgColor = "bgcolor='#FFFCF5'";
                }

                $nameHTML .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa;'>".$propertyName."</td></tr>";
                $valHTML  .= "<tr $bgColor><td border='1' style='font-size:11px; border-color:#aaa; text-align:left;'>".$propertyValue."</td></tr>";
                $j++;
            }
            $nameHTML .= "</table>";
            $valHTML .= "</table>";
            $putCsv[] = $arFields['CanCheckHistory'] ? 'yes' : 'no';
            $putCsv[] = $arFields['AutoLogin'] ? 'yes' : 'no';
            $putCsv[] = $arProviderKind[$arFields['Kind']];
            $putCsv[] = $nameHTML;
            $putCsv[] = $valHTML;
            $putCsv[] = $this->Fields['State']['Options'][$arFields['State']];
            $this->ExportExcelRow($putCsv, $i%2 == 0, false);
            $q->Next();
            $i++;
        }
        echo "</tr></table>";
    }

    function ExportExcelRow( $arValues, $grey = false, $head = false){
        $bgColorRow = "bgcolor='#FFFCF5'";
        if($grey)
            $bgColorRow = "bgcolor='#F5F2EB'";
        if($head)
            $bgColorRow = "bgcolor='#F63636' style='color:white;'";

        echo "<tr {$bgColorRow}><td style='vertical-align: middle; font-size: 12px;'>";
        $sSeparator = "</td><td style='vertical-align: middle;'>";
        echo implode($sSeparator, $arValues);
        echo "</td></tr>";
    }

	private function loadHistoryFields($providerFields){
        $fields = [];
        $checker = getSymfonyContainer()->get(\AwardWallet\MainBundle\Service\CheckerFactory::class)->getAccountChecker($providerFields['Code'], true);
        $connection = getSymfonyContainer()->get('database_connection');
        $connection->setTransactionIsolation(TransactionIsolationLevel::READ_UNCOMMITTED);
        $historyColumns = $checker->GetHistoryColumns();
        if(is_array($historyColumns)){
            foreach($historyColumns as $columnName => $destination){
                $maxAge = self::HISTORY_AGE;

                switch($destination){
                    case 'MerchantID':
                    case 'Note':
                    case 'Description':
                        continue(2);
                        break;

                    // string values
                    case 'Category':
                        $sql = "
                            SELECT
                                ah.{$destination} as Value
                            FROM Account a
                            JOIN AccountHistory ah on a.AccountID = ah.AccountID
                            WHERE
                                a.ProviderID = :providerId AND
                                a.UpdateDate > ADDDATE(NOW(), INTERVAL - :maxAge DAY) AND
                                ah.{$destination} is not null and
                                (
                                    ah.{$destination} <> '' and
                                    ah.{$destination} <> '-' and
                                    ah.{$destination} <> '--' and
                                    ah.{$destination} <> '---' and
                                    ah.{$destination} <> 'n/a'
                                )
                            LIMIT 1
                        ";
                        $params = [
                            ':maxAge' => [$maxAge, \PDO::PARAM_INT],
                            ':providerId' => [$providerFields['ProviderID'], \PDO::PARAM_INT]
                        ];
                        break;

                    // numeric values
                    case 'Amount':
                    case 'Miles':
                    case 'MilesBalance':
                    case 'AmountBalance':
                    case 'PostingDate':
                    case 'Currency':
                        $destination = ('Currency' === $destination) ? 'CurrencyID' : $destination;
                        $sql = "
                            SELECT
                                ah.{$destination} as Value
                            FROM Account a
                            JOIN AccountHistory ah on a.AccountID = ah.AccountID
                            WHERE
                                a.ProviderID = :providerId AND
                                a.UpdateDate > ADDDATE(NOW(), INTERVAL - :maxAge DAY) AND
                                ah.{$destination} is not null
                            LIMIT 1
                        ";
                        $params = [
                            ':maxAge' => [$maxAge, \PDO::PARAM_INT],
                            ':providerId' => [$providerFields['ProviderID'], \PDO::PARAM_INT]
                        ];
                        break;
                    default:
                        $serialized = 's:' . strlen($columnName) . ":\"{$columnName}\"";
                        $params = [
                            ':maxAge' => [$maxAge, \PDO::PARAM_INT],
                            ':providerId' => [$providerFields['ProviderID'], \PDO::PARAM_INT],
                            ':searchSelect' => ["{$serialized};s:", \PDO::PARAM_STR],
                            ':like1' => [$serialized . ';s:', \PDO::PARAM_STR],
                        ];

                        $sqlParts = [];
                        foreach ([
                                $serialized . ';s:0:""',
                                $serialized . ';s:3:"n/a"',
                                $serialized . ';s:3:"N/A"',
                                $serialized . ';s:1:"-"',
                                $serialized . ';s:2:"--"',
                                $serialized . ';s:3:"---"'
                            ] as $i => $notLikeSearch)
                        {
                            $sqlParts[] = " NOT LOCATE(:notLike{$i}, ah.Info) ";
                            $params[":notLike{$i}"] = [$notLikeSearch, \PDO::PARAM_STR];
                        }
                        $sqlParts = implode(' AND ', $sqlParts);
                        $sql = "
                            SELECT
                                substring_index(substring_index(substring_index(Info, :searchSelect, -1), '\";', 1), ':\"', -1) as Value
                            FROM Account a
                            JOIN AccountHistory ah on ah.AccountID = a.AccountID
                            WHERE
                                a.ProviderID = :providerId AND
                                a.UpdateDate > ADDDATE(NOW(), INTERVAL - :maxAge DAY) AND
                                LOCATE(:like1, ah.Info) AND
                                {$sqlParts}
                            LIMIT 1";
                }
                $statement = $connection->prepare($sql);
                foreach ($params as $key => &$param) {
                    $statement->bindParam($key, $param[0], $param[1]);
                } unset($params);
                $statement->execute();
                $row = $statement->fetch();
                if($row){
                    $fields[$columnName] = $this->filterAttribute($columnName, $destination, $row['Value']);
                }
            }

        }
        return $fields;
    }

    private function filterAttribute($attrName, $destination, $attrValue){
        // replace private data with default values
        if(isset($this->privateAttributes[$attrName])){
            switch($this->privateAttributes[$attrName]){
                case self::RANDOM_NAME:
                    return $this->randomNames[array_rand($this->randomNames)];
                case self::RANDOM_NUMBER:
                    return strtoupper(substr(hash('sha256', RandomStr(0, 255, 16)), 0, 8));
            }
        }
        if(
            is_numeric($attrValue) &&
            \preg_match('/^[0-9]{9,}$/', \trim($attrValue)) &&
            !\preg_match('/Phone/ims', $attrName) &&
            (
                preg_match('/Date(time)?/ims', $attrName) ||
                (
                    (int)$attrValue > 631152000/* unix Mon, 01 Jan 1990*/ &&
                    (int)$attrValue < 2524608000
                )
            )
        ){
            return date('Y-m-d', $attrValue);
        }

        if (
            ('CurrencyID' === $destination) &&
            (isset($this->currenciesData[$attrValue])) &&
            StringUtils::isNotEmpty($this->currenciesData[$attrValue])
        ) {
            return $this->currenciesData[$attrValue];
        }

        return $attrValue;
    }

	private function calcTiers(){
		$q = new TQuery("select count(*) as Cnt from Provider where WSDL = 1 and State >= ".PROVIDER_ENABLED);
		$this->tier2 = round($q->Fields["Cnt"] * 0.2);
		$this->tier3 = round($q->Fields["Cnt"] * 0.5);
	}

    private function loadCurrencies()
    {
        $stmt = getSymfonyContainer()->get('database_connection')->executeQuery("
            select 
               c.CurrencyID, 
               coalesce(c.Code, c.Name)
            from Currency c
        ");

        $this->currenciesData = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);
    }

	function FormatFields($output = "html"){
		parent::FormatFields($output);
        $arFields = & $this->Query->Fields;

		$arFields["Tier"] = "T3";
		if($this->Query->Position < $this->tier3)
			$arFields["Tier"] = "T2";
		if($this->Query->Position < $this->tier2)
			$arFields["Tier"] = "T1";

        $historyFields = $this->loadHistoryFields($arFields);

        $propNameHtml = "";
        $propValHtml  = "";
        $style = " style='border-bottom:1px solid #999; padding:2px 0; white-space:nowrap; max-width:290px; overflow:hidden;' ";
        $i = 0;
        $historyFieldsCount = count($historyFields);
        foreach($historyFields as $name => $value){
            if($i + 1 == $historyFieldsCount)
                $style = " style='white-space:nowrap; max-width:400px; overflow:hidden; padding:2px 0;' ";
            $propNameHtml .= "<div $style>".$name."</div>";
            $propValHtml  .= "<div $style>".$value."</div>";
            $i++;
        }
        //$arFields['Number'] = $this->RowCount + 1;
        $arFields['DisplayName'] = "<b>".$arFields['DisplayName']."</b>";
        $arFields['Attributes'] = $propNameHtml;
        $arFields['SampleData'] = $propValHtml;
	}

    function DrawButtonsInternal(){
        $triggers = parent::DrawButtonsInternal();
        echo "<input id=\"ExportId\" class='button' style='display: none;' type=button value=\"Export to Excel\" onclick=\"location.href = 'historyPropertiesSchedule.php?{$_SERVER['QUERY_STRING']}&export=1'\"> ";
        $triggers[] = array('ExportId', 'Export to Excel');
        return $triggers;
    }

    public function AddFilters($sSQL){
        global $arProviderStateForWsdlClients;
   		$sql = parent::AddFilters($sSQL);
   		return str_replace('State = 14', 'State IN(' . implode(', ', $arProviderStateForWsdlClients) . ')', $sql);
   	}

}
