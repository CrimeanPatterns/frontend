<?php

namespace AwardWallet\MobileBundle\View;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

abstract class AbstractBlock implements \JsonSerializable
{
    use FilterNull;

    /**
     * @var string
     */
    public $type;

    public function __construct()
    {
        $parts = explode('\\', static::class);
        $this->setType(lcfirst(array_pop($parts)));
    }

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }
}
