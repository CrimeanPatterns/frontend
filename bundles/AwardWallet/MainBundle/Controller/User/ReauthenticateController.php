<?php

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/security/reauthenticate")
 */
class ReauthenticateController
{
    /**
     * @var ReauthenticatorWrapper
     */
    private $reauthenticator;

    public function __construct(ReauthenticatorWrapper $reauthenticator)
    {
        $this->reauthenticator = $reauthenticator;
    }

    /**
     * @Route("/start", name="aw_reauth_start", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function startAction()
    {
        try {
            return $this->json($this->reauthenticator->start());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException();
        }
    }

    /**
     * @Route("/verify", name="aw_reauth_verify", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     */
    public function verifyAction()
    {
        try {
            return $this->json($this->reauthenticator->verify());
        } catch (\InvalidArgumentException $e) {
            throw new BadRequestHttpException();
        }
    }

    private function json(object $data): Response
    {
        return JsonResponse::create(array_filter((array) $data, function ($val) {
            return !is_null($val);
        }));
    }
}
