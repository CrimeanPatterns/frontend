<?php

namespace AwardWallet\Manager\Schema\Itinerary;

abstract class AbstractSchema extends \TBaseSchema
{
    public function TuneList(&$list)
    {
        parent::TuneList($list);

        $list->PageSizes = ['50' => '50', '100' => '100', '500' => '500'];
        $list->PageSize = 100;
    }

    protected function getProviderOptions(): array
    {
        return SQLToArray("SELECT ProviderID, ShortName FROM Provider ORDER BY ShortName", 'ProviderID', 'ShortName');
    }
}
