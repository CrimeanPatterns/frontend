<?php

namespace AwardWallet\MainBundle\Service;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Twig\Environment;

class MobileAppRedirector
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function createRedirect(string $url, ?Request $targetRequest = null): RedirectResponse
    {
        if ($targetRequest && $this->shouldUseForgedRedirect($targetRequest)) {
            return $this->createForgedRedirect($url);
        }

        return new RedirectResponse($url);
    }

    private function shouldUseForgedRedirect(Request $targetRequest): bool
    {
        $requestMatcher = new RequestMatcher();
        $patterns = [
            '^/gmail-forwarding$',
            '^/user/subscription/lock-in-price$',
        ];

        foreach ($patterns as $pattern) {
            $requestMatcher->matchPath($pattern);

            if ($requestMatcher->matches($targetRequest)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Prevent using the redirect response for the subscription lock-in price page
     * as iOS app will intercept the redirect and open the page in the app.
     */
    private function createForgedRedirect(string $url): RedirectResponse
    {
        $response = new RedirectResponse($url);
        $response->headers->remove('Location');
        $response->setStatusCode(200);
        $response->setContent($this->twig->render('@AwardWalletMain/redirectPage.html.twig', [
            'url' => $url,
        ]));

        return $response;
    }
}
