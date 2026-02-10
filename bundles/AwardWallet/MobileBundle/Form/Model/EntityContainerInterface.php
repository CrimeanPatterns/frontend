<?php

namespace AwardWallet\MobileBundle\Form\Model;

interface EntityContainerInterface
{
    /**
     * @param object $entity
     * @return $this
     */
    public function setEntity($entity);

    /**
     * @return object
     */
    public function getEntity();
}
