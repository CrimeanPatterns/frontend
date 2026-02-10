<?php

namespace AwardWallet\MainBundle\Timeline\Item;

use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Tripsegment;

/**
 * TODO: need to test.
 */
class Transfer extends AbstractTrip
{
    public function __construct(Tripsegment $tripsegment, ?Provider $provider = null)
    {
        parent::__construct($tripsegment, $provider);

        if ($tripsegment->getDepgeotagid() && $tripsegment->getArrgeotagid()) {
            $map = new Map([
                $tripsegment->getDepgeotagid()->getDMSformat(),
                $tripsegment->getArrgeotagid()->getDMSformat(),
            ], $tripsegment->getArrivalDate());

            if ($tripsegment->getDepcode() !== null && $tripsegment->getArrcode() !== null) {
                $map->setStationCodes([$tripsegment->getDepcode(), $tripsegment->getArrcode()]);
            }

            $this->setMap($map);
        }
    }

    public function getIcon(): string
    {
        return Icon::WAY;
    }

    public function getType(): string
    {
        return Type::TRANSFER;
    }
}
