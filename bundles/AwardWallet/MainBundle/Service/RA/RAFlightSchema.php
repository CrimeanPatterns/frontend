<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightSchema extends \TBaseSchema
{
    public const FLIGHT_TYPE_HOME = 1;
    public const FLIGHT_TYPE_PARTNER = 2;
    public const FLIGHT_TYPE_MIX = 3;

    public const FLIGHT_TYPES = [
        self::FLIGHT_TYPE_HOME => 'Home',
        self::FLIGHT_TYPE_PARTNER => 'Partner',
        self::FLIGHT_TYPE_MIX => 'Mix',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->ListClass = RAFlightList::class;
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();

        unset($result['ShortName']);

        $result['SearchDate'] = [
            'Type' => 'datetime',
        ];
        $result['DepartureDate'] = [
            'Type' => 'datetime',
        ];
        $result['ArrivalDate'] = [
            'Type' => 'datetime',
        ];

        foreach ($result as $field => $params) {
            $result[$field]['FilterField'] = 'RAFlight.' . $field;
        }

        return $result;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = function () {
            header('Location: /manager/list.php?Schema=RAFlight');
        };
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === "Provider") {
            return SQLToArray("SELECT Code, CONCAT(ShortName,' (',Code,')') AS ShortName FROM Provider WHERE CanCheckRewardAvailability <> 0", "Code", "ShortName");
        }

        if ($field === "ToRegion" || $field === "FromRegion") {
            return SQLToArray("SELECT Name FROM Region WHERE Kind = 1", "Name", "Name");
        }

        if ($field === "FlightType") {
            return [1 => 'Home', 2 => 'Partner', 3 => 'Mix'];
        }

        if ($field === "StandardItineraryCOS") {
            return [
                'economy' => 'economy',
                'premiumEconomy' => 'premiumEconomy',
                'business' => 'business',
                'firstClass' => 'firstClass',
            ];
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
