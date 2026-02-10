<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\AwCache;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Statistics;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @AwCache(expires="+1 hour", maxage="3600", etagContentHash="sha256")
 */
class StaticPagesController extends AbstractController
{
    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/about")
     */
    public function aboutAction(Request $request, Statistics $statistics)
    {
        return $this->render('@AwardWalletMain/SiteInfo/aboutUs.html.twig',
            array_merge($statistics->getOverallStat($request), ['heading' => ''])
        );
    }

    /**
     * @Route("/privacy")
     */
    public function privacyAction()
    {
        return $this->render('@AwardWalletMain/SiteInfo/privacyNotice.html.twig');
    }

    /**
     * @Route("/terms")
     */
    public function termsAction()
    {
        return $this->render('@AwardWalletMain/SiteInfo/termsOfUse.html.twig');
    }
}
