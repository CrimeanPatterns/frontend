<?php

namespace AwardWallet\MainBundle\Event\PushNotification\Target;

class ItineraryUpdate
{
    /**
     * @var bool
     */
    private $isNew;
    /**
     * @var object
     */
    private $entity;
    /**
     * @var int
     */
    private $itemsCount;

    /**
     * ItineraryUpdate constructor.
     *
     * @param object $entity
     * @param int $itemsCount
     * @param bool $isNew
     */
    public function __construct($entity, $itemsCount, $isNew)
    {
        $this->isNew = $isNew;
        $this->entity = $entity;
        $this->itemsCount = $itemsCount;
    }

    /**
     * @return bool
     */
    public function isNew()
    {
        return $this->isNew;
    }

    /**
     * @param bool $isNew
     * @return ItineraryUpdate
     */
    public function setNew($isNew)
    {
        $this->isNew = $isNew;

        return $this;
    }

    /**
     * @return object
     */
    public function getEntity()
    {
        return $this->entity;
    }

    /**
     * @param object $entity
     * @return ItineraryUpdate
     */
    public function setEntity($entity)
    {
        $this->entity = $entity;

        return $this;
    }

    /**
     * @return int
     */
    public function getItemsCount()
    {
        return $this->itemsCount;
    }

    /**
     * @param int $itemsCount
     * @return ItineraryUpdate
     */
    public function setItemsCount($itemsCount)
    {
        $this->itemsCount = $itemsCount;

        return $this;
    }
}
