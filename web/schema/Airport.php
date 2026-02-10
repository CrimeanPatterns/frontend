<?

class TAirportSchema extends TBaseSchema
{
    function __construct()
    {
        parent::TBaseSchema();
        $this->TableName = "AirCode";
        $this->Fields = array(
            "AirCode" => array(
                "Type" => "string",
                "Size" => 3,
            ),
            "AirName" => array(
                "Type" => "string",
                "Size" => 80,
            ),
            "IcaoCode" => array(
                "Type" => "string",
                "Size" => 4,
            ),
            "FS" => array(
                "Type" => "string",
                "Size" => 4,
            ),
            "FAA" => array(
                "Type" => "string",
                "Size" => 4,
            ),
            "Classification" => array(
                "Type" => "integer",
            ),
            "CityCode" => array(
                "Type" => "string",
                "Size" => 3,
            ),
            "CityName" => array(
                "Type" => "string",
                "Size" => 40,
            ),
            "CountryCode" => array(
                "Type" => "string",
                "Size" => 3,
            ),
            "CountryName" => array(
                "Type" => "string",
                "Size" => 40,
            ),
            "State" => array(
                "Type" => "string",
                "Size" => 4,
            ),
            "StateName" => array(
                "Type" => "string",
                "Size" => 40,
            ),
            "Lat" => array(
                "Type" => "float",
            ),
            "Lng" => array(
                "Type" => "float",
            ),
            "TimeZoneLocation" => array(
                "Type" => "string",
            ),
        );
    }

    function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->ReadOnly = true;
    }

    function CreateForm()
    {
        throw new \Exception("read only schema");
    }
}
