<?php

namespace AwardWallet\MainBundle\Controller\Auth;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class CapitalcardsApiEmulatorController
{
    /**
     * @Security("is_granted('SITE_DEV_MODE')")
     * @Route("/oauth2/authorize", host="capitalcards.%host%")
     */
    public function authorizeAction(Request $request)
    {
        $action = $request->query->getAlpha("action");

        if ($action === "yes") {
            return new RedirectResponse($request->query->get("redirect_uri") . "?" . http_build_query(["state" => $request->query->get("state"), "code" => "success"]));
        }

        if ($action === "no") {
            return new RedirectResponse($request->query->get("redirect_uri") . "?" . http_build_query(["state" => $request->query->get("state"), "error" => "some_error_code", "error_description" => "Some error message"]));
        }

        return new Response("Capital One Auth Emulator. Authorize? 
   	        <a href=\"?" . http_build_query(array_merge($request->query->all(), ["action" => "yes"])) . "\">Yes</a>  
   	        <a href=\"?" . http_build_query(array_merge($request->query->all(), ["action" => "no"])) . "\">No</a>
        ");
    }

    /**
     * @Security("is_granted('SITE_DEV_MODE')")
     * @Route("/oauth2/token", host="capitalcards.%host%")
     */
    public function tokenAction(Request $request)
    {
        if ($request->request->get("code") === 'success') {
            return new JsonResponse([
                'access_token' => 'accTokenSuccess',
                'refresh_token' => 'refTokenSuccess',
                'expires_in' => time() + 86400,
            ]);
        }

        throw new BadRequestHttpException('some error');
    }
}
