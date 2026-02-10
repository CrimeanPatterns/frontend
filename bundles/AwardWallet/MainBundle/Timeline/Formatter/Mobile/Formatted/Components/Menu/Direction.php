<?php

namespace AwardWallet\MainBundle\Timeline\Formatter\Mobile\Formatted\Components\Menu;

use AwardWallet\Common\Entity\Geotag;

class Direction
{
    /**
     * @var string
     */
    public $lat;

    /**
     * @var string
     */
    public $lng;

    /**
     * @var string
     */
    public $address;

    public function __construct(Geotag $geotag)
    {
        $this->lat = $geotag->getLat();
        $this->lng = $geotag->getLng();
        $this->address = $geotag->getFoundaddress();
    }
}
