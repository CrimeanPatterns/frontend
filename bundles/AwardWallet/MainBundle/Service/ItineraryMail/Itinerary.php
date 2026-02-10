<?php

namespace AwardWallet\MainBundle\Service\ItineraryMail;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Entity;

/**
 * @NoDI()
 */
class Itinerary
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var \DateTime
     */
    protected $changeDate;

    /**
     * @var Entity\Itinerary
     */
    protected $entity;

    /**
     * @var Segment[]
     */
    protected $segments = [];

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     * @return \DateTime
     */
    public function getChangeDate()
    {
        return $this->changeDate;
    }

    /**
     * @param \DateTime $changeDate
     */
    public function setChangeDate($changeDate)
    {
        $this->changeDate = $changeDate;
    }

    /**
     * @return Segment[]
     */
    public function getSegments()
    {
        return $this->segments;
    }

    /**
     * @param Segment[] $segments
     */
    public function setSegments($segments)
    {
        $this->segments = $segments;
    }

    public function getEntity()
    {
        return $this->entity;
    }

    public function setEntity(Entity\Itinerary $entity)
    {
        $this->entity = $entity;
    }
}
