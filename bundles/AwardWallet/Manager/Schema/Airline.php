<?php

namespace AwardWallet\Manager\Schema;

class Airline extends \TBaseSchema
{
    public function __construct()
    {
        parent::__construct();
        $this->Fields['Active']['Type'] = 'boolean';
        $this->Fields['ICAO']['Caption'] = 'ICAO';
        $this->Fields['FSCode']['Caption'] = 'FS Code';
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);
        // $list->ReadOnly = true;
        $list->AllowDeletes = false;
    }
}
