<?php

namespace AwardWallet\MainBundle\Entity;

use AwardWallet\Common\Entity\Geotag;

interface GeotagInterface
{
    /**
     * @return Geotag[]
     */
    public function getGeoTags();
}
