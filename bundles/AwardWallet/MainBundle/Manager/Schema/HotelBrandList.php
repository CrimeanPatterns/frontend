<?php

namespace AwardWallet\MainBundle\Manager\Schema;

class HotelBrandList extends \TBaseList
{
    public function FormatFields($output = "html")
    {
        parent::FormatFields($output);

        $this->Query->Fields['Patterns'] = '
<div style="max-width: 300px; overflow: hidden; text-overflow: ellipsis;">
    ' . nl2br($this->Query->Fields['Patterns']) . '
</div>
';
    }
}
