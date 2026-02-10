<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Entity\Repositories\ParameterRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

class PricingController extends AbstractController
{
    /**
     * @Route("/pricing", name="aw_pricing", defaults={"_canonical"="aw_pricing_locale", "_alternate"="aw_pricing_locale"}, options={"expose"=true})
     * @Route("/{_locale}/pricing", name="aw_pricing_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_pricing_locale", "_alternate"="aw_pricing_locale"}, options={"expose"=true})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function indexAction(
        Request $request,
        Environment $twigEnv,
        ParameterRepository $parameterRepository
    ): Response {
        $twigEnv->addGlobal('webpack', true);

        if (null !== ($ref = $request->query->get('ref'))
            && 10 === strlen($ref)
            && !empty($hash = $request->query->get('hash'))
        ) {
            $prePaymentQuery = ['ref' => $ref, 'hash' => $hash];
        }

        return $this->render('@AwardWalletMain/Page/pricing.html.twig', [
            'miles' => $parameterRepository->getMilesCount(),
            'prePaymentQuery' => $prePaymentQuery ?? [],
        ]);
    }
}
