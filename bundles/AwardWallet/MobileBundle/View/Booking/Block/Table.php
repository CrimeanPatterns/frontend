<?php

namespace AwardWallet\MobileBundle\View\Booking\Block;

use AwardWallet\MobileBundle\View\AbstractBlock;

class Table extends AbstractBlock
{
    /**
     * @var string[]
     */
    public $headers;

    /**
     * @var array
     */
    public $rows;

    private $headers_show = [];

    public function __construct(array $headers)
    {
        parent::__construct();
        $this->setHeaders($headers);
    }

    /**
     * @param string[] $headers
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        $this->headers_show = array_fill(0, sizeof($headers), false);
    }

    public function addRow(array $row)
    {
        $this->rows[] = $row;

        foreach ($row as $num => $field) {
            if (!is_null($field)) {
                $this->headers_show[$num] = true;
            }
        }
    }

    public function removeEmptyColumns()
    {
        foreach ($this->headers_show as $num => $show) {
            if ($show) {
                continue;
            }
            unset($this->headers[$num]);

            foreach ($this->rows as $k => $row) {
                unset($this->rows[$k][$num]);
            }
        }
        $this->headers = array_merge($this->headers);

        foreach ($this->rows as $k => $row) {
            $this->rows[$k] = array_merge($row);
        }
    }
}
