<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightList extends \TBaseList
{
    public function __construct($table, $fields, $defaultSort = null, ?\Symfony\Component\HttpFoundation\Request $request = null)
    {
        parent::__construct($table, $fields, $defaultSort, $request);
        $this->SQL = /** @lang MySQL */ "
            SELECT RAFlight.*, Provider.ShortName FROM RAFlight LEFT JOIN Provider ON RAFlight.Provider = Provider.Code 
        ";

        $this->repeatHeadersEveryNthRow = 18;
    }

    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $this->Query->Fields["Route"] = $this->formatRoute($this->Query->Fields["Route"]);
        $this->Query->Fields["MileCost"] = number_format($this->Query->Fields["MileCost"]);
        $this->Query->Fields["CostPerHour"] = number_format($this->Query->Fields["CostPerHour"]);
    }

    private function formatRoute(string $route): string
    {
        $parts = explode(",", $route);
        $result = "";
        $needLineBreak = false;

        foreach ($parts as $index => $part) {
            if ($index > 0) {
                $stopover = in_array(substr($part, 0, 3), ["rt:", "so:"]);

                if ($stopover) {
                    $part = "<b>$part</b>";
                }

                if ($stopover || $needLineBreak) {
                    $result .= "<br/>";
                } else {
                    $result .= ", ";
                }
                $needLineBreak = $stopover;
            }
            $result .= $part;
        }

        return $result;
    }
}
