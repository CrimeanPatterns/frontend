<?php

namespace AwardWallet\MainBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/")
 */
class MediaController extends AbstractController
{
    /**
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route(
     *     "/media/logos",
     *     name="aw_media_logos",
     *     options={"expose"=true},
     *     defaults={"_canonical" = "aw_media_logos_locale", "_alternate" = "aw_media_logos_locale"}
     * )
     * @Route(
     *     "/{_locale}/media/logos",
     *     name="aw_media_logos_locale",
     *     defaults={"_locale"="en", "_canonical" = "aw_media_logos_locale", "_alternate" = "aw_media_logos_locale"},
     *     requirements={"_locale" = "%route_locales%"}
     * )
     * @Template("@AwardWalletMain/Media/mediaLogos.html.twig")
     */
    public function mediaLogosAction()
    {
        return [];
    }
}
