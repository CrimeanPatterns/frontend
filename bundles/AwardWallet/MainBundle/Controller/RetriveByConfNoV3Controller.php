<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Provider;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class RetriveByConfNoV3Controller
{
    /**
     * @Route("/retrieve/confirmation/v3/get-autologin-connection/{providerId}", name="aw_retrive_by_confno_v3", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"id"="providerId"})
     * @throws \Doctrine\DBAL\DBALException
     */
    public function retrieveAction(
        Provider $provider,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        if (!$authorizationChecker->isGranted('ADD', $provider)) {
            return new JsonResponse(["error" => "forbidden"]);
        }

        if (!$provider->getCancheckconfirmation()) {
            return new JsonResponse(["error" => "bad_request"]);
        }
    }
}
