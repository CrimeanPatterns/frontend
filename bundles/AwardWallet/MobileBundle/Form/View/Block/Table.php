<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

class Table extends BaseBlock
{
    public $rows = [];

    public function __construct(array $rows = [])
    {
        $this->setType('table');
        $this->rows = $rows;
    }

    public function addRow($row)
    {
        $this->rows[] = $row;

        return $this;
    }
}
