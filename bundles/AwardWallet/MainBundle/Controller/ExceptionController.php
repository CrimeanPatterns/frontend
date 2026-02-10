<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\UserErrorException;
use AwardWallet\MainBundle\Security\Voter\SiteVoter;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExceptionController extends AbstractController implements TranslationContainerInterface
{
    public function flatErrorAction(
        FlattenException $exception,
        ?DebugLoggerInterface $_,
        Request $request,
        TranslatorInterface $translator,
        ?SiteVoter $siteVoter,
        AwTokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        $format = $request->getRequestFormat();
        $statusCode = $exception->getStatusCode();
        $headers = $exception->getHeaders();
        $exceptionClass = $exception->getClass();

        if (
            is_a($exceptionClass, ImpersonatedException::class, true)
            || (
                $siteVoter
                && $siteVoter->isImpersonationSandboxEscaped()
            )
            || (
                ($requestMatcher = new RequestMatcher('^/manager/'))
                && $requestMatcher->matches($request)
                && (403 === $statusCode)
                && $siteVoter
                && ($token = $tokenStorage->getToken())
                && $siteVoter->isImpersonated($token, $token->getUser())
            )
        ) {
            $exception = new ImpersonatedException();
            $exceptionClass = ImpersonatedException::class;
        }

        if ($exception instanceof ImpersonatedException) {
            $statusCode = 403;
        }

        $text = Response::$statusTexts[$statusCode];

        // send liteweight json error responses for new mobile app
        $requestMatcher = new RequestMatcher();
        $requestMatcher->matchPath('^/m/api');

        if ($requestMatcher->matches($request) && (false !== strpos($request->headers->get('Accept'), 'application/json'))) {
            $response = new JsonResponse(['error' => $text], $statusCode);
        } else {
            $masterRequest = $requestStack->getMasterRequest();
            $token = $tokenStorage->getToken();

            if ($token) {
                $user = $token->getUser();
            } else {
                $user = null;
            }

            $params = [
                'status_code' => $statusCode,
                'status_text' => $text,
                'user' => $user,
            ];

            if (in_array($statusCode, [400, 401, 402, 403, 404, 500])) {
                $params['message'] = $translator->trans(/** @Ignore */ "error.server." . $statusCode . ".text");
                $params['title'] = $translator->trans(/** @Ignore */ "error.server." . $statusCode . ".title");
            } else {
                $params['message'] = $translator->trans('error.server.other.text', ['%code%' => $statusCode, '%text%' => $text]);
                $params['title'] = $translator->trans('error.server.other.title');
            }

            if (is_a($exceptionClass, UserErrorException::class, true)) {
                $params['message'] = $exception->getMessage();
                $params['title'] = $translator->trans('error.server.other.title');
            }

            if ($statusCode == 404) {
                $template = '@AwardWalletMain/Exception/404.html.twig';
                $params['webpack'] = true; // for disable requirejs scripts
            } else {
                $template = '@AwardWalletMain/Exception/flatErrorNew.html.twig';
            }

            if (in_array($masterRequest->attributes->get('_route'), ['aw_booking_view_index', 'aw_booking_share_index']) && $user) {
                if ($statusCode == 403) {
                    $params['message'] = $translator->trans('error.booking-view.403.text' /** @Desc('You are attempting to access a booking request that belongs to a different account than the one you are logged in as right now. If you opened this link by mistake, please navigate to another page, if you know this is your booking request then you must login as a user to whom this request belongs. You are currently logged in as: "%user_login%", please <a href="%logout_url%">log out</a> and login again. If you are coming to this page from an email you received try using that email address as your login value, if you are unsure what username you used when you created this booking request.' */ , ['%user_login%' => $user->getLogin(), '%logout_url%' => $this->generateUrl("aw_users_logout")]);
                }
            }

            if (in_array($masterRequest->attributes->get('_route'), ['aw_account_redirect']) && $user) {
                if ($statusCode == 403) {
                    $params['message'] = $translator->trans('account.error.access.denied', ['%user-name%' => $user->getLogin()]);
                }
            }

            if (in_array($masterRequest->attributes->get('_route'), ['aw_at201_landing', 'aw_at201_landing_locale', 'aw_at201_payment'])) {
                if ($statusCode == 404) {
                    $params['message'] = 'Unfortunately, you are not eligible to pay for the AwardTravel 201 membership';
                }
            }

            $request->setRequestFormat($format);

            if ($request->isXmlHttpRequest() || stripos($request->getContentType(), 'json') !== false) {
                $response = $this->json(['message' => $params['message'], 'title' => $params['title']]);
            } else {
                $response = $this->render(
                    $template,
                    $params
                );
            }

            if (!empty($headers)) {
                $response->headers->replace($headers);
            }
        }
        $response->setStatusCode($statusCode);

        return $response;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('error.server.400.text'))->setDesc('The server returned error 400 - "Bad Request". We apologize for this inconvenience, please navigate to some other page.'),
            (new Message('error.server.401.text'))->setDesc('The server returned error 401 - "Authorization Required". We apologize for this inconvenience, please navigate to some other page.'),
            (new Message('error.server.402.text'))->setDesc('The server returned error 402 on this page. We apologize for this inconvenience, please navigate to some other page.'),
            (new Message('error.server.403.text'))->setDesc('The server returned error 403 - "Forbidden". We apologize for this inconvenience, please navigate to some other page.'),
            (new Message('error.server.404.text'))->setDesc('The server returned error 404 - "Page Not Found". We apologize for this inconvenience, please navigate to some other page.'),
            (new Message('error.server.500.text'))->setDesc('The server returned error 500 - "Internal Server Error". We apologize for this inconvenience, please navigate to some other page.'),

            (new Message('error.server.400.title'))->setDesc('Bad Request'),
            (new Message('error.server.401.title'))->setDesc('Authorization Required'),
            (new Message('error.server.402.title'))->setDesc('Payment Required'),
            (new Message('error.server.403.title'))->setDesc('Forbidden'),
            (new Message('error.server.404.title'))->setDesc('Page Not Found'),
            (new Message('error.server.500.title'))->setDesc('Internal Server Error'),
        ];
    }

    /**
     * @Route("/ajax_error.gif", name="record_ajax_error", options={"expose"=true})
     * @return Response
     */
    public function recordAjaxErrorAction(Request $request, LoggerInterface $logger)
    {
        $user = $this->getUser();
        $page = $request->headers->get('referer', 'Empty Referer');
        $url = $request->query->get('url', 'unknown');
        $status = $request->query->get('status', 'unknown');
        $method = $request->query->get('method', 'unknown');
        $req = $request->query->get('req', 'unknown');
        $res = $request->query->get('res', 'unknown');
        $requestLevel = $request->query->get('logLevel');
        $message = $request->query->get('message', '');
        $host = $request->headers->get('host');
        $ip = $request->getClientIp();
        $session = $request->getSession() ? substr($request->getSession()->getId(), -4) : 'unknown';
        $browser = $request->headers->get('user-agent', 'unknown');

        if (\is_null($requestLevel)) {
            if ($status == '403' || $status == '404') {
                $level = Logger::ERROR;
            } else {
                $level = Logger::CRITICAL;
            }
        } else {
            $loggerLevels = Logger::getLevels();
            $level = $loggerLevels[\strtoupper($requestLevel)] ?? Logger::INFO;
        }
        // @see #11303 #12171
        $logger->log($level, 'ajax error' . ($message ? ': ' . $message : ''), [
            'RequestURL' => $url,
            'RequestMethod' => $method,
            'RequestData' => substr($req, 0, 2) . '...' . substr($req, -2),
            'ResponseStatus' => $status,
            'ResponseData' => $res,
            'UserID' => ($user ? $user->getUserid() : null),
            'Page' => $page,
            'Host' => $host,
            'IP' => $ip,
            'Session' => $session,
            'UA' => $browser,
        ]);

        return new Response(base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw=='), 200, ['Content-Type' => 'image/gif']);
    }

    /**
     * @Route("/js_error", name="record_js_error", options={"expose"=true})
     * @return Response
     */
    public function recordJSErrorAction(Request $request, LoggerInterface $logger)
    {
        /** @var Usr $user */
        $user = $this->getUser();
        $page = $request->headers->get('referer', 'Empty Referer');
        $host = $request->headers->get('host');
        $ip = $request->getClientIp();
        $session = $request->getSession() ? substr($request->getSession()->getId(), -4) : 'unknown';
        $browser = $request->headers->get('user-agent', 'unknown');

        $logger->warning('javascript error: ' . json_encode($request->request->all()), [
            'UserID' => ($user ? $user->getId() : null),
            'Page' => $page,
            'Host' => $host,
            'IP' => $ip,
            'Session' => $session,
            'UA' => $browser,
        ]);

        return new Response('ok');
    }

    /**
     * @Route("/error/{code}", name="show_error", requirements={"code" = "[45]\d\d"})
     * @param int $code
     * @return Response
     */
    public function showError(
        $code,
        Request $request,
        TranslatorInterface $translator,
        SiteVoter $siteVoter,
        AwTokenStorageInterface $tokenStorage,
        RequestStack $requestStack
    ) {
        return $this->flatErrorAction(
            new FlattenException(new HttpException($code)),
            "html",
            $request,
            $translator,
            $siteVoter,
            $tokenStorage,
            $requestStack
        );
    }

    final private function getFromRequest(ParameterBag $request, string $key): string
    {
        $result = $request->getAlnum($key);

        if (!is_string($result)) {
            return 'unknown';
        }

        return $result;
    }
}
