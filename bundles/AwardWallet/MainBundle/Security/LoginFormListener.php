<?php

namespace AwardWallet\MainBundle\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;

class LoginFormListener extends UsernamePasswordFormAuthenticationListener
{
    protected function attemptAuthentication(Request $request)
    {
        if (empty($request->request->get("_csrf_token")) && $request->headers->has("X-XSRF_TOKEN")) {
            $request->request->set("_csrf_token", $request->headers->get("X-XSRF_TOKEN"));
        }

        try {
            return parent::attemptAuthentication($request);
        } catch (InvalidCsrfTokenException $e) {
            $request->attributes->set("csrf_failed", true);

            throw $e;
        }
    }
}
