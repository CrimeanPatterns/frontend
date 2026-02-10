<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Service\PopularityHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class SupportedProgramsController extends AbstractController
{
    private PopularityHandler $popularityHandler;

    public function __construct(PopularityHandler $popularityHandler)
    {
        $this->popularityHandler = $popularityHandler;
    }

    /**
     * @Route("/supported-programs-seo", name="aw_supported_seo")
     */
    public function indexAction()
    {
        global $arProviderKind;

        $providers = $this->popularityHandler->getPopularPrograms(null, " AND p.ProviderID NOT IN ({$this->popularityHandler->unsupportedProviders})");
        $providerKinds = [];

        foreach ($arProviderKind as $id => $name) {
            $providerKinds[] = (object) [
                'id' => $id,
                'name' => $name,
            ];
        }

        return $this->render('@AwardWalletMain/SupportedPrograms/index.html.twig', [
            'providers' => $providers,
            'kinds' => $providerKinds,
        ]);
    }

    /**
     * @Route("/r", name="aw_r_supported_programms_prefix")
     */
    public function rRedirectAction(Request $request)
    {
        return $this->redirectToRoute('aw_home');
    }

    /**
     * @Route(
     *     "/supported-programs",
     *     name="aw_supported",
     *     defaults={"_canonical" = "aw_supported_locale", "_alternate" = "aw_supported_locale"}
     * )
     * @Route(
     *     "/{_locale}/supported-programs",
     *     name="aw_supported_locale",
     *     defaults={"_locale"="en"},
     *     requirements={"_locale" = "%route_locales%", "_canonical" = "aw_supported_locale", "_alternate" = "aw_supported_locale"}
     * )
     */
    public function listAction()
    {
        return $this->render('@AwardWalletMain/SupportedPrograms/list.html.twig', [
            'providers' => $this->popularityHandler->getPopularPrograms(null, " AND p.ProviderID NOT IN ({$this->popularityHandler->unsupportedProviders})"),
        ]);
    }
}
