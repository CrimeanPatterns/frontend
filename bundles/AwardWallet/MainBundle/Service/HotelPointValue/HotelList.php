<?php

namespace AwardWallet\MainBundle\Service\HotelPointValue;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class HotelList extends \TBaseList
{
    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        if ($output === "html") {
            $this->Query->Fields["Matches"] = it(json_decode($this->OriginalFields["Matches"], true))
                ->map(function (array $row) {
                    return "<a href=\"/manager/list.php?Schema=HotelPointValue&HotelPointValueID={$row['HotelPointValueID']}\" target=\"_blank\">{$row['HotelPointValueID']}</a>";
                })
                ->joinToString(", ")
            ;
        }
    }
}
