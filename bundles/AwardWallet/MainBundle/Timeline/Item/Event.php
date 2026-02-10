<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Restaurant;

class Event extends AbstractItinerary implements CanCreatePlanInterface
{
    use CanCreatePlanTrait;

    protected string $icon;

    public function __construct(Restaurant $event)
    {
        parent::__construct(
            $event->getId(),
            $event->getUTCStartDate(),
            $event->getUTCEndDate(),
            $event->getStartdate(),
            $event,
            $event->getConfirmationNumber(true),
            $event->getAccount(),
            $event->getProvider(),
            $event->getGeotagid(),
            null,
            !empty($event->getChangedate())
        );
        $icon = Icon::RESTAURANT;

        switch ($event->getEventtype()) {
            case Restaurant::EVENT_MEETING:
                $icon = Icon::MEETING;

                break;

            case Restaurant::EVENT_SHOW:
                $icon = Icon::SHOWS;

                break;

            case Restaurant::EVENT_EVENT:
                $icon = Icon::EVENT;

                break;

            case Restaurant::EVENT_RESTAURANT:
                $icon = Icon::RESTAURANT;

                break;

            case Restaurant::EVENT_CONFERENCE:
                $icon = Icon::CONFERENCE;

                break;

            case Restaurant::EVENT_RAVE:
                $icon = Icon::RAVE;

                break;
        }

        $this->icon = $icon;
    }

    public function getPrefix(): string
    {
        return Restaurant::getSegmentMap()[0];
    }

    public function getType(): string
    {
        return Type::RESTAURANT;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }
}
