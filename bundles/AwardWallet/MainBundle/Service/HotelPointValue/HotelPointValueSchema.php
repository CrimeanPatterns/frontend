<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

class HotelPointValueSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->TableName = "HotelPointValue";
        $this->Fields = [
            "HotelPointValueID" => [
                "Type" => "integer",
                "Caption" => "ID",
                "Size" => 10,
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
                "Sort" => "HotelPointValueID DESC",
            ],
            "ProviderID" => [
                "Caption" => "Provider",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "Options" => SQLToArray("select p.ProviderID, p.DisplayName from Provider p 
                    where p.Kind = " . PROVIDER_KIND_HOTEL . " and " . userProviderFilter()
                    . " order by ShortName",
                    "ProviderID", "DisplayName"),
                "FilterField" => "HotelPointValue.ProviderID",
            ],
            "ReservationID" => [
                "Type" => "integer",
                "Caption" => "Reservation ID",
                "InplaceEdit" => false,
                "FilterField" => "HotelPointValue.ReservationID",
            ],
            "UserID" => [
                "Type" => "integer",
                "Caption" => "User ID",
                "InplaceEdit" => false,
            ],
            "HotelName" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
                "FilterField" => "HotelPointValue.HotelName",
            ],
            "BrandID" => [
                "Caption" => "Brand",
                "Type" => "integer",
                "Required" => false,
                "InplaceEdit" => true,
                "ReadOnly" => false,
                "Options" => ['' => ''] + SQLToArray("select HotelBrandID, Name from HotelBrand",
                    "HotelBrandID", "Name"),
                "FilterField" => "HotelPointValue.BrandID",
            ],
            "Address" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
                "FilterField" => "HotelPointValue.Address",
            ],
            "CheckInDate" => [
                "Type" => "date",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "HotelPointValue.CheckInDate",
            ],
            "CheckOutDate" => [
                "Type" => "date",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "HotelPointValue.CheckOutDate",
            ],
            "GuestCount" => [
                "Caption" => "Guests",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 10,
                "FilterField" => "HotelPointValue.GuestCount",
            ],
            "KidsCount" => [
                "Caption" => "Kids",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 10,
                "FilterField" => "HotelPointValue.KidsCount",
            ],
            "RoomCount" => [
                "Caption" => "Rooms",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 10,
                "FilterField" => "HotelPointValue.RoomCount",
            ],
            "UpdateDate" => [
                "Type" => "datetime",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "FilterField" => "HotelPointValue.UpdateDate",
            ],
            "SpentAwards" => [
                "Caption" => "Spent<br/>Awards<br/>",
                "Type" => "string",
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "Total" => [
                "Caption" => "Reservation<br/>Total<br/>",
                "Type" => "string",
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "CurrencyCode" => [
                "Caption" => "Reservation<br/>Currency<br/>",
                "Type" => "string",
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "TotalPointsSpent" => [
                "Caption" => "Total<br/>Points<br/>",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "TotalTaxesSpent" => [
                "Caption" => "Total<br/>Taxes",
                "Type" => "integer",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "AlternativeHotelName" => [
                "Caption" => "Alternative<br/>Hotel",
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 40,
            ],
            "AlternativeCost" => [
                "Caption" => "Alternative<br/>Cost",
                "Type" => "float",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            "PointValue" => [
                "Type" => "float",
                "Caption" => "Point<br/>Value",
                "Required" => true,
                "InplaceEdit" => false,
                "ReadOnly" => true,
                "filterWidth" => 20,
            ],
            'SourceCheck' => [
                'Type' => 'string',
                "InplaceEdit" => false,
                'ReadOnly' => true,
                'Required' => false,
                'Database' => false,
            ],
            "Status" => [
                "Type" => "string",
                "Required" => true,
                "InplaceEdit" => true,
                "ReadOnly" => true,
                "filterWidth" => 50,
                "Options" => \AwardWallet\MainBundle\Service\MileValue\CalcMileValueCommand::STATUSES,
            ],
            "Note" => [
                "Type" => "string",
                "Required" => false,
                "InplaceEdit" => true,
                "ReadOnly" => false,
                "filterWidth" => 130,
                "InputType" => "textarea",
                "InputAttributes" => "style='height: 50px; width: 130px;'",
            ],
        ];
        $this->bIncludeList = false;
        $this->ListClass = HotelPointValueList::class;

        // for export / import
        ini_set('memory_limit', '1G');
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        $list->MultiEdit = true;
        $list->InplaceEdit = true;
        $list->CanAdd = false;
        $list->repeatHeadersEveryNthRow = 10;
    }

    public function GetFormFields()
    {
        $result = parent::GetFormFields();

        unset($result['UserID']);
        unset($result['SpentAwards']);

        return $result;
    }
}
