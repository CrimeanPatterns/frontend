<?php

namespace AwardWallet\MainBundle\Service\FlightStats\TripAlerts;

use AwardWallet\MainBundle\DependencyInjection\Annotation\NoDI;
use AwardWallet\MainBundle\Service\Notification\Content;

/**
 * @NoDI()
 */
class Push
{
    /**
     * @var array
     */
    public $deviceTypes;
    /**
     * @var Content
     */
    public $content;

    public function __construct(array $deviceTypes, Content $content)
    {
        $this->deviceTypes = $deviceTypes;
        $this->content = $content;
    }
}
