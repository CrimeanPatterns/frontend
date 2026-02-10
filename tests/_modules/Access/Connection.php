<?php

namespace AwardWallet\Tests\Modules\Access;

use AwardWallet\MainBundle\Entity\Useragent;

class Connection
{
    public $approved = false;
    public $accessLevel = Useragent::ACCESS_NONE;

    public function __construct($approved = false, $accessLevel = Useragent::ACCESS_NONE)
    {
        $this->approved = $approved;
        $this->accessLevel = $accessLevel;
    }
}
