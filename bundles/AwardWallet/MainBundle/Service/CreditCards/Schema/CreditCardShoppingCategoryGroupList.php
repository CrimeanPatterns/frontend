<?php

namespace AwardWallet\MainBundle\Service\CreditCards\Schema;

class CreditCardShoppingCategoryGroupList extends \TBaseList
{
    public function getRowColor(): string
    {
        $result = parent::getRowColor();

        if ($this->Query->Fields['Active'] == 0) {
            $result = '#eedddd';
        }

        return $result;
    }
}
