<?

use AwardWallet\MainBundle\Entity\Fee;
use AwardWallet\MainBundle\Entity\Room;
use AwardWallet\MainBundle\Globals\StringUtils;
use function AwardWallet\MainBundle\Globals\Utils\iter\explodeLazy;
use function AwardWallet\MainBundle\Globals\Utils\iter\toArray;
use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;
use function AwardWallet\MainBundle\Globals\Utils\stmt\stmtAssoc;

$schema = "tripSchedule";
require( "../start.php" );

class TripScheduleList extends TBaseList{

    // format output data
    const FORMAT_DATE = 1;
    const FORMAT_DESERIALIZE = 2;
    const FORMAT_ROOMS = 61;
    const FORMAT_FEES = 62;

    const FORMAT_TRUNCATE = 3;
    const FORMAT_TRUNCATE_DEFAULT = 60;

    // generate random data
    const RANDOM_NUMBER = 1;
    const RANDOM_NAME = 2;
    const RANDOM_NAMES = 3;
    const RANDOM_NUMBERS = 4;

    private const GROUP_CONCAT_SEPARATOR = '~%#$@~';

    private $randomNames = ['John Smith', 'Kate Smith', 'John Doe', 'Kate Doe', 'John Johnson', 'Kate Johnson'];

    // itinerary updated or created ITINERARY_AGE days ago
    const ITINERARY_AGE = 7;

    public  $customAge = null;
    public  $customProviders = [];

	private $tier2;
	private $tier3;
	private $attributes;

    // hides from output
    private $excludedAttributes = [
        'UserAgentID',
        'Copied',
        'Frequent Flyer Number',
        'FrequentFlyerNumber',
        'NoItineraries'
    ];

    // filled with default values
    private $privateAttributes = [
    ];

    private $attributesMap = [
        'L' => [
            'Table' => 'Rental',
            'DisplayName' => 'Rental Cars',
            // Main properties, selected from corresponding "Table"
            'Fields' => [
                'Number',

                'PickupDatetime',
                'PickupLocation',
                'PickupPhone',
                'PickupHours',

                'DropoffDatetime',
                'DropoffLocation',
                'DropoffPhone',
                'DropoffHours',
            ],
            // some options for formatting output values
            'Format' => [
                //'ReservationDate' => self::FORMAT_DATE,
                'Fees' => [self::FORMAT_FEES => null],
            ],
            // replaced by default value
            'Private' => [
                'Number' => self::RANDOM_NUMBER,
                'RenterName' => self::RANDOM_NAME,
                'PromoCode' => self::RANDOM_NUMBER,
                'AccountNumbers' => self::RANDOM_NUMBERS,
                'AccountNumber' => self::RANDOM_NUMBER,
                'TravelAgencyConfirmationNumbers' => self::RANDOM_NUMBERS,
                'ConfirmationNumbers' => self::RANDOM_NUMBERS,
                'ParsedAccountNumbers' => self::RANDOM_NUMBERS,
                'TravelerNames' => self::RANDOM_NAMES,
            ],
            // do not show this fields
            'Excluded' => [
                'Discounts',
            ],
            'ExtProperties' => [
                // common itinerary
                'Cost',
                'CurrencyCode',
                'Discount',
                'SpentAwards',
                'EarnedAwards',
                'Total',
                'TravelAgencyConfirmationNumbers',
                'ParsedStatus',
                'Tax',
                'Fees',
                'ConfirmationNumbers',
                'CurrencyCode',
                'ParsedAccountNumbers',
                // direct copy
                'CarImageUrl',
                'CarModel',
                'CarType',
                'DropOffFax',
                'PickUpFax',
                'ProviderName',
                'TravelerNames',
            ]
        ],
        'T' => [
            'Table' => 'Trip',
            'DisplayName' => 'Flights',
            'Fields' => [
                'RecordLocator',
            ],
            'Private' => [
                'RecordLocator' => self::RANDOM_NUMBER,
                'ConfirmationNumbers' => self::RANDOM_NUMBERS,
                'Passengers' => self::RANDOM_NAMES,
                'AccountNumbers' => self::RANDOM_NUMBERS,
                'AccountNumber' => self::RANDOM_NUMBER,
                'TravelAgencyConfirmationNumbers' => self::RANDOM_NUMBERS,
                'ParsedAccountNumbers' => self::RANDOM_NUMBERS,
                'TravelerNames' => self::RANDOM_NAMES,
                'TicketNumbers' => self::RANDOM_NUMBERS,
            ],
            'ExtProperties' => [
                // common itinerary
                'Cost',
                'CurrencyCode',
                'Discount',
                'SpentAwards',
                'EarnedAwards',
                'Total',
                'TravelAgencyConfirmationNumbers',
                'ParsedStatus',
                'Tax',
                'Fees',
                'ConfirmationNumbers',
                'CurrencyCode',
                'ParsedAccountNumbers',
                // direct copy
                'CabinClass',
                'CabinNumber',
                'ShipCode',
                'ShipName',
                'CruiseName',
                'Deck',
                // custom 
                'TravelerNames',
                'TicketNumbers',
            ],
            'Format' => [
                'Fees' => [self::FORMAT_FEES => null],
            ]
        ],
        'S' => [
            'Table' => 'TripSegment',
            'DisplayName' => 'Flights',
            'Fields' => [
                'DepCode',
                'DepName',
                'DepDate',
                'ArrCode',
                'ArrName',
                'ArrDate',
                'AirlineName',
                'FlightNumber'
            ],
            'Private' => [
                'Passengers' => self::RANDOM_NAME,
            ],
            'ExtProperties' => [
                // direct copy
                'Aircraft',
                'ArrivalGate',
                'DepartureGate',
                'ArrivalTerminal',
                'DepartureTerminal',
                'BaggageClaim',
                'CabinClass',
                'Duration',
                'Meal',
                'Smoking',
                'Stops',
                'TraveledMiles',
                // custom
                'Seats',
                'BookingClass',
            ]
        ],
        'R' => [
            'Table' => 'Reservation',
            'DisplayName' => 'Hotels',
            'Fields' => [
                'HotelName',
                'CheckInDate',
                'CheckOutDate',
                'ConfirmationNumber',
                'Address',
                'Phone',
            ],
            'Private' => [
                'ConfirmationNumber' => self::RANDOM_NUMBER,
                'ConfirmationNumbers' => self::RANDOM_NUMBERS,
                'GuestNames' => self::RANDOM_NAMES,
                'GuestName' => self::RANDOM_NAME,
                'TravelerNames' => self::RANDOM_NAMES,
                'ParsedAccountNumbers' => self::RANDOM_NUMBERS,
                'TravelAgencyConfirmationNumbers' => self::RANDOM_NUMBERS,
            ],
            'Format' => [
                'CancellationPolicy' => [self::FORMAT_TRUNCATE => self::FORMAT_TRUNCATE_DEFAULT],
                'RoomTypeDescription' => [self::FORMAT_TRUNCATE => self::FORMAT_TRUNCATE_DEFAULT],
                'Address' => [self::FORMAT_TRUNCATE => self::FORMAT_TRUNCATE_DEFAULT],
                'Fees' => [self::FORMAT_FEES => null],
                'Rooms' => [self::FORMAT_ROOMS => null],
            ],
            'ExtProperties' => [
                // common itinerary
                'Cost',
                'CurrencyCode',
                'Discount',
                'SpentAwards',
                'EarnedAwards',
                'Total',
                'TravelAgencyConfirmationNumbers',
                'ParsedStatus',
                'Tax',
                'Fees',
                'ConfirmationNumbers',
                'CurrencyCode',
                'ParsedAccountNumbers',
                // direct copy 
                'CancellationPolicy',
                'Fax',
                'RoomCount',
                // custom
                'GuestCount',
                'KidsCount',
                'TravelerNames',
                'Rooms',
            ]
        ],
        'E' => [
            'Table' => 'Restaurant',
            'DisplayName' => 'Events',
            'Fields' => [
                'ConfNo',
                'Name',
                'Address',
                'Phone',
                'StartDate',
            ],
            'Private' => [
                'ConfNo' => self::RANDOM_NUMBER,
                'DinnerName' => self::RANDOM_NAME,
                'DinnerNames' => self::RANDOM_NAME,
                'AccountNumbers' => self::RANDOM_NUMBER,
                'TravelerNames' => self::RANDOM_NAMES,
                'ParsedAccountNumbers' => self::RANDOM_NUMBERS,
                'TravelAgencyConfirmationNumbers' => self::RANDOM_NUMBERS,
                'ConfirmationNumbers' => self::RANDOM_NUMBERS,
            ],
            'Format' => [
                'Address' => [self::FORMAT_TRUNCATE => self::FORMAT_TRUNCATE_DEFAULT],
                'Fees' => [self::FORMAT_FEES => null],
            ],
            'ExtProperties' => [
                // common itinerary
                'Cost',
                'CurrencyCode',
                'Discount',
                'SpentAwards',
                'EarnedAwards',
                'Total',
                'TravelAgencyConfirmationNumbers',
                'ParsedStatus',
                'Tax',
                'Fees',
                'ConfirmationNumbers',
                'CurrencyCode',
                'ParsedAccountNumbers',
                // direct copy
                'Phone',
                // custom
                'GuestCount',
                'TravelerNames',
            ]
        ],
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
            "CanCheckItinerary" => array(
                "Type" => "boolean",
                "Caption" => "Travel Itineraries",
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
			p.CanCheckItinerary,
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
		$this->PageSizes["5000"] = "5000";
		$this->PageSize = 5000;
		$this->ExportName = "ReservationProperties";
	}

    function ExportExcel(){
        global $arProviderKind, $arDeepLinking;

        $this->ExportName = "ReservationProperties";
        header("Content-type: text/xls; charset=utf-8");
        header("Content-Disposition: attachment; filename={$this->ExportName}.xls");

        echo "<table cellspacing='0' cellpadding='3' border='1'>";
        if($this->ExportCSVHeader){
            $captions = array();
            $captions[] = "#";
            $captions[] = '<b style="font-size:13px;">Provider Code</b>';
            $captions[] = '<b style="font-size:13px;">Display Name</b>';
            $captions[] = '<b style="font-size:13px;">AutoLogin</b>';
            $captions[] = '<b style="font-size:13px;">Kind</b>';

            $captions[] = '
                <table cellpadding="3" border="1">
                    <tr>
                        <td style="vertical-align: middle; font-size: 13px; color:white;">
                            <b>Properties</b>
                        </td>
                        <td style="vertical-align: middle; font-size: 13px; color:white;">
                            <b>Sample Data</b>
                        </td>
                    </tr>
                </table>';
            $this->ExportExcelRow( $captions, true, true );
        }

        $this->OpenQuery();
        $q = &$this->Query;
        $i = 0;
        while(!$q->EOF){
            $arFields = $q->Fields;
            $props = $this->loadItineraryAttributes($arFields['ProviderID']);

            $putCsv = array();
            $putCsv[] = "<div style='text-align:center'>".($i + 1)."</div>";
			$putCsv[] = $arFields['Code'];
            $putCsv[] = "<b>".$arFields['DisplayName']."</b>";
            $nameHTML = $valHTML = $kindHTML = "<table border='1' style='border-color:#aaa;'>";
            $j = 0;
            $kindsCount = count($props);
            if(!empty($props)){
                foreach($props as $kind => $properties){
                    if($kindsCount > 1){
                        $nameHTML .= "
                            <tr>
                                <td colspan='2' align='center' border='1' style='font-weight: bold; font-size:14px; border-color:#aaa;'>
                                    ". $this->attributesMap[$kind]['DisplayName']."
                                </td>
                            </tr>";
                    }
                    foreach($properties as $propertyName => $propertyValue){
                        $bgColor = "";
                        if($j%2 != 0){
                            if($i%2 != 0)
                                $bgColor = "bgcolor='#F5F2EB'";
                            else
                                $bgColor = "bgcolor='#FFFCF5'";
                        }

                        $nameHTML .= "
                            <tr $bgColor>
                                <td border='1' style='font-size:11px; border-color:#aaa;'>".$propertyName."</td>
                                <td border='1' style='font-size:11px; border-color:#aaa; text-align:left;'>".$propertyValue."</td>
                            </tr>";
                        //$valHTML  .= "<tr $bgColor></tr>";
                        $j++;
                    }
                }
            }else{
                $nameHTML .= "<tr><td colspan='2' border='1' style='font-weight: bold; font-size:14px; border-color:#aaa;'>&nbsp;</td></tr>";
            }
            $nameHTML .= "</table>";
            //$valHTML .= "</table>";
            $putCsv[] = $arFields['AutoLogin'] ? 'yes' : 'no';
            $putCsv[] = $arProviderKind[$arFields['Kind']];
            $putCsv[] = $nameHTML;
            //$putCsv[] = $valHTML;
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

	private function loadItineraryAttributes($providerId){
        // load Main attributes
        // key => 'Value' array
        $attributes = [];
        $this->loadMainAttrbutes($attributes, $providerId);
        $this->loadExtAttributes($attributes, $providerId);
        foreach($attributes as $kind => &$kindAttributes){
            ksort($kindAttributes);
        }
        unset($kindAttributes);
        return $attributes;
	}

    private function loadExtAttributes(&$attributes, $providerId){
        $attributesArray = toArray($this->buildExtPropertyIter($providerId));

        foreach($attributesArray as $attributeRow){
            $value = $this->filterAttribute($this->attributesMap[$attributeRow['SourceTable']], $attributeRow['Name'], $attributeRow['Value']);
            if(isset($value)){
                $kind = ($attributeRow['SourceTable'] == 'S') ? 'T' : $attributeRow['SourceTable'];
                $attributes[$kind][$attributeRow['Name']] = $value;
            }
        }
    }

    private function buildExtPropertyIter($providerId) : iterable {
        global $arDetailTable;
        $arDetailTable['S'] = 'TripSegment';
        unset($arDetailTable['D']);

        $connection = getSymfonyContainer()->get('database_connection');
        $connection->executeQuery('SET SESSION group_concat_max_len = 4096');

        foreach($arDetailTable as $arKind => $arTableName){
            $kindSql = $this->buildExtPropertySQLByTable($arKind, $arTableName, $providerId);
            $kindStmt = $connection->executeQuery($kindSql);

            yield from
                stmtAssoc($kindStmt)
                ->flatMap(function (array $kindRow) use ($arKind) {
                    $providerId = $kindRow['ProviderID'];
                    unset($kindRow['ProviderID']);

                    foreach (
                        it($kindRow)
                        ->chunkWithKeys(3) // _name, _count, _example triplet
                        as $propertyData
                    ) {
                        [$propertyName, $count, $examples] = \array_values($propertyData);

                        if (! (int) $count) {
                            continue;
                        }

                        $example = it(explodeLazy(self::GROUP_CONCAT_SEPARATOR, $examples, 'UTF-8'))->first();

                        if (StringUtils::isEmpty($example)) {
                            continue;
                        }

                        yield [
                            'ProviderID' => $providerId,
                            'Name' => $propertyName,
                            'Value' => $example,
                            'SourceTable' => $arKind,
                            'Count' => $count,
                        ];
                    }
                });
        }
    }

    private function buildExtPropertySQLByTable($kind, $tableName, $providerId){
        // join Trip additionally if Kind == S (TripSegments)
        $tableAlias = ('S' === $kind ? "t" : "tr");

        return "
            select 
                {$tableAlias}.ProviderID,
            " .
               it($this->attributesMap[$kind]['ExtProperties'])
               ->flatMap(function (string $property) use ($kind) : array {
                   $valueFilter = "IF(
                           tr.{$property} is not null and
                           tr.{$property} not in ('-', '--', '---', 'n/a', 'N/A', '0', '-1', 'a:0:{}', ''),
                       tr.{$property},
                       null
                   )";

                   return [
                       "'{$property}' as {$property}_name",
                       "COUNT({$valueFilter}) as {$property}_count",
                       "GROUP_CONCAT({$valueFilter} SEPARATOR '" . self::GROUP_CONCAT_SEPARATOR . "') as {$property}_example",
                   ];
               })
               ->joinToString(",\n") . "
            from {$tableName} tr
            " . ('S' === $kind ? " JOIN Trip t ON t.TripID = tr.TripID" : "") . "
            where
                {$tableAlias}.ProviderID = {$providerId} AND
                ({$tableAlias}.CreateDate > ADDDATE(NOW(), -7) or {$tableAlias}.UpdateDate > ADDDATE(NOW(), -7))
            group by {$tableAlias}.ProviderID
        ";
    }

    private function loadMainAttrbutes(&$attributes, $providerId){
        foreach($this->attributesMap as $kind => $map){
            $fields = $map['Fields'];
            $table = $map['Table'];
            foreach($fields as $field){
                $whereSql = [];
                if($kind == 'S'){
                    $whereSql[] = "Trip.ProviderID = {$providerId}";
                    $whereSql[] = "(Trip.CreateDate > ADDDATE(NOW(), -" . $this->getProviderItineraryAge($providerId) . ") OR Trip.UpdateDate > ADDDATE(NOW(), -" . $this->getProviderItineraryAge($providerId) . "))";
                    $sql = "SELECT {$table}.{$field} FROM Trip JOIN {$table} on Trip.TripID = {$table}.TripID";
                }else{
                    $whereSql[] = "{$table}.ProviderID = {$providerId}";
                    $whereSql[] = "({$table}.CreateDate > ADDDATE(NOW(), -" . $this->getProviderItineraryAge($providerId) . ") OR {$table}.UpdateDate > ADDDATE(NOW(), -" . $this->getProviderItineraryAge($providerId) . "))";
                    $sql = "SELECT {$table}.{$field} FROM {$table}";
                }
                $whereSql[] = "{$table}.$field IS NOT NULL";
                $whereSql[] = "{$table}.$field NOT IN ('-', '--', '---', 'n/a', 'N/A', '0', '-1', '')";
                $sql .= " WHERE " . implode(" AND ", $whereSql);

                $sql .= " LIMIT 1";
                $attributeQuery = new TQuery($sql);
                if(!$attributeQuery->EOF){
                    $value = $this->filterAttribute($map, $field, $attributeQuery->Fields[$field]);
                    if(isset($value)){
                        $attrKind = ($kind == 'S') ? 'T' : $kind;
                        $attributes[$attrKind][$field] = $value;
                    }
                }
            }
        }
    }


    private function filterAttribute($map, $attrName, $attrValue){
        // suppress excluded attributes
        if((isset($map['Excluded']) && in_array($attrName, $map['Excluded'], true)) || in_array($attrName, $this->excludedAttributes, true)){
            return null;
        }
        // replace private data with default values
        if(isset($map['Private'][$attrName])){
            $randomNumbersGen = function () {
                return strtoupper(substr(hash('sha256', RandomStr(0, 255, 16)), 0, 6));
            };

            switch($map['Private'][$attrName]){
                case self::RANDOM_NAME:
                    return $this->randomNames[\array_rand($this->randomNames)];
                case self::RANDOM_NAMES:
                    return
                        it((array) \array_rand($this->randomNames, \random_int(1, 2)))
                        ->map(function ($key) { return $this->randomNames[$key]; })
                        ->joinToString(', ');

                case self::RANDOM_NUMBER:
                    return $randomNumbersGen();

                case self::RANDOM_NUMBERS:
                    return
                        it(range(1, 2))
                        ->map($randomNumbersGen)
                        ->joinToString(', ');
            }
        }
        // format
        if(isset($map['Format'][$attrName])){
            foreach($map['Format'][$attrName] as $formatType => $formatSettings){
                $arrayMerger = function ($name, $value) {
                    if (StringUtils::isNotEmpty($value)) {
                        return [$name => $value];
                    } else {
                        return [];
                    }
                };
                switch($formatType){
                    case self::FORMAT_TRUNCATE:
                        if(mb_strlen($attrValue) > $formatSettings){
                            return mb_substr($attrValue, 0, $formatSettings) . ' ...';
                        }
                        break;
                    case self::FORMAT_DATE:
                        return date('Y-m-d', $attrValue);

                    case self::FORMAT_ROOMS: {
                        return
                            it(@\unserialize($attrValue) ?: [])
                            ->flatMap(function (Room $room) use ($arrayMerger) {
                                $result = \array_merge(
                                    $arrayMerger('shortDesc', $room->getShortDescription()),
                                    $arrayMerger('longDesc', $room->getLongDescription()),
                                    $arrayMerger('rate', $room->getRate()),
                                    $arrayMerger('rateDesc', $room->getRateDescription())
                                );

                                if ($result) {
                                    yield $result;
                                }
                            })
                            ->toJSON();
                    }

                    case self::FORMAT_FEES: {
                        return
                            it(@\unserialize($attrValue) ?: [])
                            ->flatMap(function (Fee $fee) use ($arrayMerger) {
                                $result = \array_merge(
                                    $arrayMerger('name', $fee->getName()),
                                    $arrayMerger('charge', $fee->getCharge())
                                );

                                if ($result) {
                                    yield $result;
                                }
                            })
                            ->toJSON();
                    }
                }
            }
        }

        if(is_numeric($attrValue) && preg_match('/Date(time)?/ims', $attrName)){
            return date('Y-m-d', $attrValue);
        }
        return $attrValue;
    }

	private function calcTiers(){
		$q = new TQuery("select count(*) as Cnt from Provider where WSDL = 1 and State >= ".PROVIDER_ENABLED);
		$this->tier2 = round($q->Fields["Cnt"] * 0.2);
		$this->tier3 = round($q->Fields["Cnt"] * 0.5);
	}

	function FormatFields($output = "html"){
		parent::FormatFields($output);
        $arFields = & $this->Query->Fields;

		$arFields["Tier"] = "T3";
		if($this->Query->Position < $this->tier3)
			$arFields["Tier"] = "T2";
		if($this->Query->Position < $this->tier2)
			$arFields["Tier"] = "T1";

        $attributes = $this->loadItineraryAttributes($arFields['ProviderID']);

        $propNameHtml = "";
        $propValHtml  = "";
        $style = " style='border-bottom:1px solid #999; padding:2px 0; white-space:nowrap; max-width:290px; overflow:hidden;' ";
        $i = $k = 0;
        $kindsCount = count($attributes);
        foreach($attributes as $kind => $kindAttributes){
            $kindAttributesCount = count($kindAttributes);
            if($kindsCount > 1){
                $propNameHtml .= "<div style='font-weight: bold;white-space:nowrap; max-width:400px; overflow:hidden; padding:2px 0;'>".$this->attributesMap[$kind]['Table']."</div>";
                $propValHtml  .= "<div style='white-space:nowrap; max-width:400px; overflow:hidden; padding:2px 0;'>&nbsp;</div>";
            }
            foreach($kindAttributes as $name => $value){
                if($i + 1 == $kindAttributesCount && $k + 1 == $kindsCount)
                    $style = " style='white-space:nowrap; max-width:400px; overflow:hidden; padding:2px 0;' ";
                $propNameHtml .= "<div $style>".$name."</div>";
                $propValHtml  .= "<div $style>".$value."</div>";
                $i++;
            }
            $k++;
        }
        //$arFields['Number'] = $this->RowCount + 1;
        $arFields['DisplayName'] = "<b>".$arFields['DisplayName']."</b>";
        $arFields['Attributes'] = $propNameHtml;
        $arFields['SampleData'] = $propValHtml;
	}

    function DrawButtonsInternal(){
        $triggers = parent::DrawButtonsInternal();
        echo "<input id=\"ExportId\" class='button' style='display: none;' type=button value=\"Export to Excel\" onclick=\"location.href = 'tripSchedule.php?{$_SERVER['QUERY_STRING']}&export=1'\"> ";
        $triggers[] = array('ExportId', 'Export to Excel');
        return $triggers;
    }

    public function AddFilters($sSQL){
        global $arProviderStateForWsdlClients;
   		$sql = parent::AddFilters($sSQL);
   		return str_replace('State = 14', 'State IN(' . implode(', ', $arProviderStateForWsdlClients) . ')', $sql);
   	}

    private function getProviderItineraryAge($providerID){
        if(isset($this->customAge) && in_array($providerID, $this->customProviders)){
            return $this->customAge;
        }else{
            return self::ITINERARY_AGE;
        }
    }

}

$list = new TripScheduleList();
if(isset($_GET['customAge'])){
    $list->customAge = intval($_GET['customAge']);
}

if(isset($_GET['customProviders'])){
    $providerIds = explode(',', $_GET['customProviders']);
    foreach($providerIds as $providerId){
        $list->customProviders[] = intval($providerId);
    }
}
if(isset($_GET['export'])){
    $list->ExportCSVHeader = true;
    $list->ExportExcel();
} else{
    drawHeader("Reservation Properties Schedule");
    $list->Draw();
    drawFooter();
}

?>
