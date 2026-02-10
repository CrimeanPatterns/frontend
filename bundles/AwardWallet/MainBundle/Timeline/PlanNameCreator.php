<?php

namespace AwardWallet\MainBundle\Timeline;

use AwardWallet\MainBundle\Entity\Itinerary;
use AwardWallet\MainBundle\Timeline\Item\ItemInterface;
use AwardWallet\MainBundle\Timeline\Item\ItineraryInterface;
use AwardWallet\MainBundle\Timeline\Util\ItineraryUtil;
use Symfony\Contracts\Translation\TranslatorInterface;

class PlanNameCreator
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * @param ItemInterface[] $segments
     * @return string
     */
    public function generateName(array $segments)
    {
        /** @var Itinerary[] $itineraries */
        $itineraries = array_filter(array_map(function ($segment) {
            if ($segment instanceof ItineraryInterface) {
                return $segment->getItinerary();
            }

            return null;
        }, $segments));
        $target = ItineraryUtil::findTarget($itineraries);

        if ($target) {
            if (count($target) > 1) {
                $name = implode(', ', array_map(function ($point) {
                    return $point->getCity();
                }, $target));
            } else {
                $name = $this->translator->trans( /** @Desc("Trip to %pointName%") */ 'trip.to', ['%pointName%' => $target[0]->getCity()]);
            }
        } else {
            $name = $this->translator->trans(/** @Desc("New Travel Plan") */ 'new.travel.plan');
        }

        return $name;
    }
}
