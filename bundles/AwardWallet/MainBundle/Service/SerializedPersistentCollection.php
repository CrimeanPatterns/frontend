<?php

namespace AwardWallet\MainBundle\Service;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @NoDI
 */
class SerializedPersistentCollection
{
    /**
     * @var array|ArrayCollection
     */
    private $elements;

    public function __construct($elements)
    {
        $this->elements = $elements;
    }

    /**
     * @return array|ArrayCollection
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * @param array|ArrayCollection $elements
     */
    public function setElements($elements)
    {
        $this->elements = $elements;
    }
}
