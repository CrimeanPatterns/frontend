<?php

namespace AwardWallet\MainBundle\Service\RA;

class RAFlightHardLimitList extends \TBaseList
{
    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);
        $this->Query->Fields["Base"] = number_format($this->Query->Fields["Base"]);
        $this->Query->Fields["Multiplier"] = number_format($this->Query->Fields["Multiplier"]);
        $this->Query->Fields["HardCap"] = number_format($this->Query->Fields["HardCap"]);
    }
}
