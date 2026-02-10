<?php

namespace AwardWallet\Engine\testprovider\History;

use AwardWallet\Engine\testprovider\Success;

class Error extends Success
{
    public function GetHistoryColumns()
    {
        return [
            "Type"            => "Info",
            "Eligible Nights" => "Info",
            "Post Date"       => "PostingDate",
            "Description"     => "Description",
            "Starpoints"      => "Miles",
            "Bonus"           => "Bonus",
        ];
    }

    public function ParseHistory($startDate = null)
    {
        throw new \Exception("Some history parsing error");
    }

    public function combineHistoryBonusToMiles()
    {
        return true;
    }
}
