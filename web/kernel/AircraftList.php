<?php

class AircraftList extends TBaseList
{
    function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $arFields = &$this->Query->Fields;
        $this->OriginalFields = $arFields;

        if (isset($arFields['Icon']) && $arFields['Icon'] && $output == 'html') {
            $arFields['Icon'] = "<i class=\"{$arFields['Icon']}\"></i>";
        }
    }

    function GetExportParams(&$arCols, &$arCaptions)
    {
        parent::GetExportParams($arCols, $arCaptions);
        unset($arCols['IataCode']);
        unset($arCaptions['IataCode']);
    }
}
