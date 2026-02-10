<?php

namespace AwardWallet\MainBundle\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Security\Http\HttpUtils;

class AuthenticationEntryPointHandler implements AuthenticationEntryPointInterface
{
    public const REQUEST_ATTRIBUTE_REDIRECT_TO_LOGIN = "redirect_to_login";
    private $httpKernel;
    private $httpUtils;
    private $loginPath;

    /**
     * Constructor.
     *
     * @param HttpUtils           $httpUtils  An HttpUtils instance
     * @param string              $loginPath  The path to the login form
     */
    public function __construct(HttpKernelInterface $kernel, HttpUtils $httpUtils, $loginPath)
    {
        $this->httpKernel = $kernel;
        $this->httpUtils = $httpUtils;
        $this->loginPath = $loginPath;
    }

    public function start(Request $request, ?AuthenticationException $authException = null)
    {
        if (!$this->isMobileSite($request)) {
            if ($request->attributes->get(self::REQUEST_ATTRIBUTE_REDIRECT_TO_LOGIN) === false) {
                return new JsonResponse('Access denied');
            }

            if ($request->isXmlHttpRequest()) {
                return new Response("unauthorized", 403, ["Ajax-Error" => "unauthorized"]);
            } else {
                return $this->httpUtils->createRedirectResponse($request, '/login?BackTo=' . urlencode($request->getRequestUri()));
            }
        } else {
            return $this->httpUtils->createRedirectResponse($request, $this->loginPath);
        }
    }

    protected function isMobileSite(Request $request)
    {
        $requestMatcher = new RequestMatcher();
        $requestMatcher->matchPath('^/(mobile|_wdt)');

        return $requestMatcher->matches($request);
    }
}
