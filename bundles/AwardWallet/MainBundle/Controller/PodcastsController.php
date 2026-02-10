<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class PodcastsController extends AbstractController
{
    /**
     * @Route("/podcast", name="aw_podcast", methods={"GET"}, options={"expose"=true})
     */
    public function podcastAction(
        Request $request,
        Environment $twigEnv,
        CustomHeadersListener $customHeadersListener,
        PageVisitLogger $pageVisitLogger
    ) {
        $twigEnv->addGlobal('webpack', true);

        $data = [];
        $customHeadersListener->addDomainsToCSPDirective(
            $request,
            [
                CustomHeadersListener::CSP_DIRECTIVE_MEDIA => [
                    'https://chtbl.com',
                    'https://www.buzzsprout.com',
                    'https://audio.buzzsprout.com',
                ],
                CustomHeadersListener::CSP_DIRECTIVE_IMG => ['https://storage.buzzsprout.com'],
                CustomHeadersListener::CSP_DIRECTIVE_CONNECT => ['https://feeds.buzzsprout.com'],
            ]
        );

        if (!$request->headers->has(MobileHeaders::MOBILE_NATIVE)) {
            $pageVisitLogger->log(PageVisitLogger::PAGE_PODCASTS);
        }

        return $this->render('@AwardWalletMain/spa.html.twig', [
            'entrypoint' => 'new-pages',
            'data' => $data,
            'renderDefaultHeader' => true,
            'renderLandingFooter' => true,
        ]);
    }
}
