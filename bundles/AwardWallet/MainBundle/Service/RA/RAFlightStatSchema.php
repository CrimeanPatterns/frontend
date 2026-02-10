<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightStatSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->TableName = "RAFlightStat";
        $this->Fields = [
            'RAFlightStatID' => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "Sort" => "RAFlightStatID DESC",
            ],
            'Provider' => [
                "Caption" => "Provider",
                "Type" => "string",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "Options" => SQLToArray("SELECT Code as Provider, CONCAT(ShortName,' (',Code,')') AS ShortName FROM Provider WHERE Kind = " . PROVIDER_KIND_AIRLINE . " ORDER BY ShortName",
                    "Provider", "ShortName"),
            ],
            'ProviderAlliance' => [
                "Caption" => "Provider Alliance",
                "Type" => "string",
                "Options" => SQLToArray("select Provider.Code, Alliance.Name from Provider LEFT JOIN Alliance ON Alliance.AllianceID = Provider.AllianceId WHERE Provider.CanCheckRewardAvailability <> 0 ORDER BY Alliance.Name",
                    "Name", "Name"),
                "FilterField" => "Alliance.Name",
            ],
            'Carrier' => [
                "Caption" => "Carrier IATA",
                "Type" => "string",
            ],
            'AirlineName' => [
                "Caption" => "Airline Name",
                "Type" => "string",
                "Options" => SQLToArray("SELECT FSCode AS Code, `Name` FROM Airline ORDER BY `Name`",
                    "Name", "Name"),
                "FilterField" => "Airline.Name",
            ],
            'AirlineAlliance' => [
                "Caption" => "Airline Alliance",
                "Type" => "string",
                "Options" => SQLToArray("SELECT Alliance.Name, Provider.IATACode AS Code FROM Alliance LEFT JOIN Provider ON Alliance.AllianceID = Provider.AllianceId AND Provider.IATACode<>'' ORDER BY Alliance.Name",
                    "Name", "Name"),
                //                "FilterField" => "AirlineAlliance.Name",
            ],
            'FirstSeen' => [
                "Caption" => "First Online",
                "Type" => "date",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
            ],
            'LastSeen' => [
                "Caption" => "Last Online",
                "Type" => "date",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
            ],
            'FirstBook' => [
                "Type" => "date",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
            ],
            'LastBook' => [
                "Type" => "date",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
            ],
        ];
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->ReadOnly = true;

        $list->SQL = /** @lang MySQL */ "
            SELECT DISTINCT RAFlightStat.*, Provider.ShortName, Airline.Name AS AirlineName, Alliance.Name AS ProviderAlliance, AirlineAlliance.Name AS AirlineAlliance 
            FROM RAFlightStat 
                LEFT JOIN Provider ON RAFlightStat.Provider = Provider.Code 
                LEFT JOIN Alliance ON Alliance.AllianceID = Provider.AllianceId 
                LEFT JOIN Airline ON RAFlightStat.Carrier = Airline.FSCode
				LEFT JOIN (
				  SELECT Alliance.Name, Provider.IATACode FROM Alliance
                  LEFT JOIN Provider ON Alliance.AllianceID = Provider.AllianceId
				) AS AirlineAlliance ON RAFlightStat.Carrier = AirlineAlliance.IATACode
            WHERE RAFlightStat.Provider<>'testprovider'
        ";

        $list->repeatHeadersEveryNthRow = 20;
    }

    public function GetExportFields()
    {
        $fields = parent::GetExportFields();
        $fields['ProviderAlliance'] = $this->Fields['ProviderAlliance'];
        $fields['AirlineName'] = $this->Fields['AirlineName'];
        $fields['AirlineAlliance'] = $this->Fields['AirlineAlliance'];
        $fields['FirstBook'] = $this->Fields['FirstBook'];
        $fields['LastBook'] = $this->Fields['LastBook'];

        return $fields;
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = function () {
            header('Location: /manager/list.php?Schema=RAFlightStat');
        };
    }
}
