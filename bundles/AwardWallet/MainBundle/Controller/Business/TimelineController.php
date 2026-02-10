<?php

namespace AwardWallet\MainBundle\Controller\Business;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;

class TimelineController
{
    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/timeline/search", name="aw_timeline_search", options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Timeline/search.html.twig")
     */
    public function searchAction()
    {
        return [];
    }

    /**
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Route("/trips/add", name="aw_business_trips_add", options={"expose"=true})
     * @Template("@AwardWalletMain/Business/Timeline/add.html.twig")
     */
    public function addAction()
    {
        return [];
    }
}
