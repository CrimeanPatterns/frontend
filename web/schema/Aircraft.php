<?php

class TAircraftSchema extends TBaseSchema
{

    function __construct()
    {
        parent::TBaseSchema();
        $this->ListClass = AircraftList::class;
        $this->TableName = "Aircraft";

        $this->Fields = [
            "IataCode" => [
                "Type" => "string",
                "Size" => 255,
                "Required" => false,
                "InputAttributes" => "readonly disabled",
            ],
            'IcaoCode' => [
                'Type' => 'string',
                'Required' => false,
                'InputAttributes' => 'readonly disabled',
            ],
            "Name" => [
                "Type" => "string",
                "Size" => 255,
                "Required" => false,
                "HTML" => true,
                "InputAttributes" => "readonly disabled",
            ],
            "TurboProp" => [
                "Type" => "boolean",
                "InputType" => "checkbox",
                "InputAttributes" => "readonly disabled",
                "Required" => true
            ],
            "Jet" => [
                "Type" => "boolean",
                "InputType" => "checkbox",
                "InputAttributes" => "readonly disabled",
                "Required" => true
            ],
            "WideBody" => [
                "Type" => "boolean",
                "InputType" => "checkbox",
                "InputAttributes" => "readonly disabled",
                "Required" => true
            ],
            "Regional" => [
                "Type" => "boolean",
                "InputType" => "checkbox",
                "InputAttributes" => "readonly disabled",
                "Required" => true
            ],
            "ShortName" => [
                "Caption" => "AW Short Name",
                "Type" => "string",
                "Size" => 255,
                "Required" => true
            ],
            "Icon" => [
                "Type" => "string",
                "Manager" => new TSelectPictureFieldManager(),
                "Size" => 255,
                "Required" => true,
                "Options" => self::getAircraftsIcons(),
            ],
        ];
    }

    static function getAircraftsIcons()
    {
        $aircrafts = [
            'icon-aircraft-default',
            'icon-aircraft-A380',
            'icon-aircraft-B748',
            'icon-aircraft-B752',
            'icon-aircraft-B739',
            'icon-aircraft-MD90',
            'icon-aircraft-E170',
            'icon-aircraft-CRJ7',
            'icon-aircraft-RJ1H',
            'icon-aircraft-A359',
            'icon-aircraft-A333',
            'icon-aircraft-A346',
            'icon-aircraft-AT72',
        ];

        return array_combine($aircrafts, $aircrafts);
    }

    function GetExportFields()
    {
        $fields = parent::GetExportFields();
        $fields['Icon'] = $this->Fields['Icon'];
        return $fields;
    }


    function GetFormFields()
    {
        $fields = parent::GetFormFields();
        return $fields;
    }

    function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
    }

}
