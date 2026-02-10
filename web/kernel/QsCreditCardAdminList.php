<?php

class QsCreditCardAdminList extends TBaseList
{
    protected function getRowColor() : string
    {
        $rowColor = parent::getRowColor();

        return $rowColor;
    }

    public function FormatFields($output = "html")
    {
        empty($this->Query->Fields['SUM_Earnings']) ?: $this->Query->Fields['SUM_Earnings'] = '$' . round($this->Query->Fields['SUM_Earnings'], 2);
        empty($this->Query->Fields['SUM_CPC']) ?: $this->Query->Fields['SUM_CPC'] = '$' . round($this->Query->Fields['SUM_CPC'], 2);
    }

    public function GetEditLinks()
    {
        //$arFields = &$this->OriginalFields;
        $links = '';

        //$links .= "<a href=edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}>Edit</a>";

        return $links;
    }

    function DrawButtons($closeTable = true)
    {
        global $Interface;
        parent::DrawButtons($closeTable);

    }
}
