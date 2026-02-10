<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess;

class SqlTask extends Task
{
    public $sql;
    public $params;

    public function __construct($sql, $params = [], $requestId)
    {
        parent::__construct("aw.async.executor.sql", $requestId);
        $this->sql = $sql;
        $this->params = $params;
    }
}
