<?php

namespace AwardWallet\Manager\Schema;

class QsUserCardsList extends \TBaseList
{
    public function FormatFields($output = 'html')
    {
        parent::FormatFields($output);

        $this->Query->Fields['FullName'] = trim($this->Query->Fields['FirstName'] . '  ' . $this->Query->Fields['LastName']);

        /*
        if (1 === (int) $this->Query->Fields['DetectedViaBank']) {
            $this->Query->Fields['DetectedType'] =
            $this->Query->Fields['DetectedViaBank'] = 'bank';
            $this->Query->Fields['DetectedViaCobrand'] = '';
        } elseif (1 === (int) $this->Query->Fields['DetectedViaCobrand']) {
            $this->Query->Fields['DetectedType'] =
            $this->Query->Fields['DetectedViaCobrand'] = 'cobrand';
            $this->Query->Fields['DetectedViaBank'] = '';
        } else {
            $this->Query->Fields['DetectedType'] = '--';
        }
        */
        if ('Yes' == $this->Query->Fields['DetectedViaBank']) {
            $this->Query->Fields['DetectedViaBank'] = 'Bank';
            $this->Query->Fields['DetectedViaCobrand'] = '';
        } elseif ('Yes' == $this->Query->Fields['DetectedViaCobrand']) {
            $this->Query->Fields['DetectedViaBank'] = '';
            $this->Query->Fields['DetectedViaCobrand'] = 'Cobrand';
        }

        $rawVars = explode('||', $this->Query->Fields['_RawVars']);
        $this->Query->Fields['RawVars'] = '<ul><li>' . implode('</li><li>', $rawVars) . '</li></ul>';

        $clicksDate = explode('||', $this->Query->Fields['_ClicksDate']);
        $this->Query->Fields['ClicksDate'] = '<ul><li>' . implode('</li><li>', $clicksDate) . '</li></ul>';

        $query = [
            'Schema' => 'Qs_Transaction',
            'User' => $this->Query->Fields['UserID'],
            'Card' => $this->Query->Fields['qtCard'],
            'startDate' => $this->Query->Fields['startDate'],
            'Sort1' => 'ClickDate',
            'SortOrder' => 'Reverse',
        ];

        $this->Query->Fields['CardName'] = '<a href="/manager/list.php?' . http_build_query($query) . '">' . $this->Query->Fields['CardName'] . '</a>';
    }
}
