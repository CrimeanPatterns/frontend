<?php

namespace AwardWallet\MobileBundle\Form\View\Block;

use AwardWallet\MainBundle\Globals\JsonSerialize\FilterNull;

abstract class BaseBlock implements \JsonSerializable
{
    use FilterNull {
        jsonSerialize as __jsonSerialize;
    }
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $group;

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param string $group
     * @return $this
     */
    public function setGroup($group)
    {
        $this->group = $group;

        return $this;
    }

    public function jsonSerialize()
    {
        if (null === $this->type) {
            throw new \UnexpectedValueException('block type should be set');
        }

        return $this->__jsonSerialize();
    }
}
