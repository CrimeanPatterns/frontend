<?php

namespace AwardWallet\MobileBundle\Form\Model;

abstract class AbstractEntityAwareModel implements EntityContainerInterface
{
    protected $entity;

    /**
     * @return $this
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    public function getEntity()
    {
        return $this->entity;
    }
}
