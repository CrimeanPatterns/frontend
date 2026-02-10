<?php

namespace AwardWallet\MainBundle\Service\Account\Model\ExpiredAccount;

class Group
{
    private $items = [];

    private $separator = '';

    public function __construct(array $items, $separator = '')
    {
        $this->items = $items;
        $this->separator = $separator;
    }

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }
}
