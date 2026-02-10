<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\SocksMessaging\AccessCheckHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class SocksController extends AbstractController
{
    use JsonTrait;

    /**
     * @Route("/centrifuge/auth/", name="aw_socks_auth", methods={"POST"})
     */
    public function authAction(
        Request $request,
        AccessCheckHandler $accessCheckHandler,
        AntiBruteforceLockerService $awSecurityAntibruteforceCentrifugeChannelAuth
    ): JsonResponse {
        $authRequest = \json_decode($request->getContent());

        if (!\is_object($authRequest)) {
            throw new BadRequestHttpException('Invalid auth request');
        }

        if (null !== $awSecurityAntibruteforceCentrifugeChannelAuth->checkForLockout($request->getClientIp())) {
            return $this->jsonResponse($accessCheckHandler->makeFailResult($authRequest));
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        return $this->jsonResponse($accessCheckHandler->checkAuth($authRequest));
    }
}
