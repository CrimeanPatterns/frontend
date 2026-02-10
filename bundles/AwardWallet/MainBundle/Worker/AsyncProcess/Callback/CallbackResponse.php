<?php

namespace AwardWallet\MainBundle\Worker\AsyncProcess\Callback;

use AwardWallet\MainBundle\Worker\AsyncProcess\Response;

class CallbackResponse extends Response
{
    public $data;

    public function __construct($data)
    {
        $this->data = $data;
        $this->status = self::STATUS_READY;
    }
}
