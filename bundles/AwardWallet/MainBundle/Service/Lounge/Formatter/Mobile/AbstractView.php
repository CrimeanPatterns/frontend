<?php

namespace AwardWallet\MainBundle\Service\Lounge\Formatter\Mobile;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

abstract class AbstractView implements \JsonSerializable
{
    use FilterNull;
}
