<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

class HotelSchema extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();

        unset(
            $this->Fields["GeoTagID"],
            $this->Fields['AlternativeData'],
            $this->Fields['GooglePlaceDetails'],
            $this->Fields['GoogleUpdateDate'],
            $this->Fields['GoogleUpdateAttempts']
        );
    }

    public function GetListFields()
    {
        $result = parent::GetListFields();
        unset($result['AlternativeData'], $result['GooglePlaceDetails']);

        $result['Lat'] = [
            'Type' => 'float',
        ];
        $result['Lng'] = [
            'Type' => 'float',
        ];

        foreach ($result as $field => $params) {
            if (in_array($field, ['Lat', 'Lng'])) {
                continue;
            }
            $result[$field]['FilterField'] = 'a.' . $field;
        }

        return $result;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        // $list->SQL = "select Hotel.*, GeoTag.Lat, GeoTag.Lng from Hotel join GeoTag on Hotel.GeoTagID = GeoTag.GeoTagID";

        $fields = $this->Fields;
        unset($fields['Matches']);

        $list->SQL = "
            SELECT a.*, j.Matches
            FROM (
                SELECT
                        h." . implode(', h.', array_keys($fields)) . ",
                        gt.Lat, gt.Lng
                FROM Hotel h
                JOIN GeoTag gt ON (h.GeoTagID = gt.GeoTagID)
            ) a JOIN Hotel j ON (j.HotelID = a.HotelID)
        ";
    }

    public function GetFormFields()
    {
        $fields = parent::GetFormFields();
        unset($fields['Matches']);

        return $fields;
    }

    public function TuneForm(\TBaseForm $form)
    {
        parent::TuneForm($form);
        $form->OnSave = function () {
            header('Location: /manager/list.php?Schema=Hotel');
        };
    }

    protected function guessFieldOptions(string $field, array $fieldInfo): ?array
    {
        if ($field === "ProviderID") {
            return SQLToArray("select ProviderID, ShortName from Provider where ProviderID in (10, 12, 17, 22)", "ProviderID", "ShortName");
        }

        if ($field === "GeoTagID") {
            return [];
        }

        return parent::guessFieldOptions($field, $fieldInfo);
    }
}
