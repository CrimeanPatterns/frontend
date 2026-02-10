<?php

namespace AwardWallet\Manager\Schema;

class BlogLinkClick extends \TBaseSchema
{
    public function __construct()
    {
        parent::TBaseSchema();

        $this->Fields['MID']['Caption'] = 'MID';
        $this->Fields['CID']['Caption'] = 'CID';
    }

    public function TuneList(&$list): void
    {
        parent::TuneList($list);

        $list->ShowExport =
        $list->ShowImport =
        $list->CanAdd =
        $list->MultiEdit = false;
    }
}
