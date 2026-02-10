<?php

namespace AwardWallet\MainBundle\Service\RA\Flight\Schema;

use AwardWallet\MainBundle\Service\EnhancedAdmin\AbstractEnhancedSchema;

class RAFlightSearchRouteSchema extends AbstractEnhancedSchema
{
    public function __construct()
    {
        parent::__construct();

        $this->ListClass = RAFlightSearchRouteList::class;
    }

    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->ReadOnly = false;
        $list->ShowImport = false;
        $list->ShowExport = false;
        $list->AllowDeletes = false;
        $list->CanAdd = false;
        $list->DefaultSort = 'Segments';
    }
}
