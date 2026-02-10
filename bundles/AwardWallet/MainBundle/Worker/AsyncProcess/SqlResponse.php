<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class SqlResponse extends Response
{
    /**
     * @var array
     */
    public $rows;

    /**
     * @var array
     */
    public $columns = [];

    public function __construct(array $rows)
    {
        $this->rows = array_map(function ($row) { return array_map(function ($val) { return strval($val); }, array_values($row)); }, $rows);
        $this->status = self::STATUS_READY;

        if (count($rows) > 0) {
            foreach (array_keys($rows[0]) as $key) {
                $this->columns[] = NameToText($key);
            }
        }
    }
}
