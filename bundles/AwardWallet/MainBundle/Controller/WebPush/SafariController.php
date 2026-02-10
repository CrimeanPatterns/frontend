<?php

namespace AwardWallet\MainBundle\Controller\WebPush;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Service\WebPush\SafariPackageBuilder;
use AwardWallet\MainBundle\Service\WebPush\SafariTokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class SafariController extends AbstractController
{
    private LoggerInterface $logger;
    private EntityManagerInterface $entityManager;
    private AuthorizationCheckerInterface $authorizationChecker;
    private AwTokenStorageInterface $tokenStorage;
    private MobileDeviceManager $mobileDeviceManager;
    private SafariTokenGenerator $tokenGenerator;

    public function __construct(
        LoggerInterface $logger,
        EntityManagerInterface $entityManager,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorageInterface $tokenStorage,
        MobileDeviceManager $mobileDeviceManager,
        SafariTokenGenerator $tokenGenerator
    ) {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->tokenGenerator = $tokenGenerator;
    }

    /**
     * @Route("/safari/v1/pushPackages/{webPushId}", name="aw_push_safari_package", methods={"POST"})
     */
    public function safariPackageAction(
        string $webPushId,
        Request $request,
        SafariPackageBuilder $packageBuilder,
        string $webpushIdParam
    ) {
        if (empty($params = json_decode($request->getContent(), true)) || empty($params["token"])) {
            throw new BadRequestHttpException('userToken required');
        }

        if ($webPushId !== $webpushIdParam) {
            throw new BadRequestHttpException('Invalid webpush id');
        }

        $userToken = $params['token'];
        $host = $request->getHost();

        if ($request->headers->has('x-original-host')) {
            // handle ngrok connection
            $host = $request->headers->get('x-original-host');
        }

        $file = $packageBuilder->build($userToken, $host);

        $response = new Response();
        $response->headers->set('Content-Type', 'application/zip');
        $response->headers->set("Content-disposition", "attachment; filename=" . $webPushId . ".zip");
        $response->headers->set('Pragma', 'public');
        $response->headers->set('Expires', '0');
        $response->headers->set('Cache-Control', 'must-revalidate, post-check=0, pre-check=0');
        $response->headers->set('Cache-Control', 'private');

        $response->sendHeaders();

        $response->setContent(file_get_contents($file));
        unlink($file);
        //        $response->headers->set('File-Location', $file);
        $this->logger->info("safari package", ["file" => $file]);

        return $response;
    }

    /**
     * @Route("/safari/v1/log", name="aw_push_safari_log", methods={"POST"})
     */
    public function safariLogsActions(Request $request)
    {
        $this->logger->error("safari web push error", json_decode($request->getContent(), true));

        return new Response();
    }

    /**
     * @Route("/safari/v1/devices/{deviceToken}/registrations/{webPushId}", name="aw_push_safari_register", methods={"POST", "DELETE"}, requirements={"deviceToken": "\w+"})
     * @param string $deviceToken
     */
    public function safariRegisterAction(Request $request, $deviceToken, $webPushId)
    {
        if (
            $this->authorizationChecker->isGranted('USER_IMPERSONATED')
            || $this->authorizationChecker->isGranted('USER_IMPERSONATED_AS_SUPER')
        ) {
            return new Response();
        }

        $user = $this->getUserFromHeaders($request);
        $this->logger->warning("safari push register", ["method" => $request->getMethod(), "userId" => ($user ? $user->getUserid() : null), "deviceToken" => $deviceToken]);

        if ($request->getMethod() == 'POST') {
            $this->addDevice(
                $user,
                'safari',
                $deviceToken,
                $request->getLocale(),
                $request->getClientIp(),
                $request->getSession(),
                $request->headers->get('user-agent')
            );
        } else {
            $this->mobileDeviceManager->removeDeviceByKey($deviceToken, 'safari');
        }

        return new Response();
    }

    /**
     * @Route("/safari/save-device-token/{deviceToken}", name="aw_push_safari_save_token", methods={"POST"}, requirements={"deviceToken": "\w+"}, options={"expose": true})
     * @param string $deviceToken
     */
    public function safariSaveDeviceToken(Request $request, $deviceToken)
    {
        if (
            $this->authorizationChecker->isGranted('USER_IMPERSONATED')
            || $this->authorizationChecker->isGranted('USER_IMPERSONATED_AS_SUPER')
        ) {
            return new Response();
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (!($user instanceof Usr)) {
            $user = null;
        }

        $this->addDevice($user, 'safari', $deviceToken, $request->getLocale(), $request->getClientIp(), $request->getSession());

        return new Response();
    }

    /**
     * @Route("/safari/get-user-token", name="aw_push_safari_get_user_token", methods={"GET"}, options={"expose": true})
     */
    public function safariGetUserTokenAction(SafariTokenGenerator $tokenGenerator)
    {
        return new JsonResponse($tokenGenerator->getToken());
    }

    private function addDevice(?Usr $user = null, $device, $deviceToken, $locale, $ip, ?SessionInterface $session = null, ?string $userAgent = null)
    {
        return $this->mobileDeviceManager->addDevice(
            $user ? $user->getUserid() : null,
            $device,
            $deviceToken,
            $locale,
            'web:' . ($this->authorizationChecker->isGranted('SITE_BUSINESS_AREA') ? 'business' : 'personal'),
            $ip,
            $session,
            true,
            $userAgent
        );
    }

    /**
     * @param string $token
     * @return Usr
     */
    private function getUserFromToken($token)
    {
        $this->logger->warning("safari get user from token", ["token" => $token]);

        if (!preg_match('#^' . preg_quote(SafariTokenGenerator::USER_PREFIX) . ':(\w+):(\w+)$#ims', $token, $matches)) {
            throw new AccessDeniedHttpException();
        }

        /** @var Usr $user */
        if ($matches[1] == 'anonymous') {
            $user = null;
        } else {
            $user = $this->entityManager->getRepository(Usr::class)->find($matches[1]);

            if (empty($user)) {
                throw new AccessDeniedHttpException();
            }
        }

        $validToken = $this->tokenGenerator->getUserToken($user);

        if ($validToken != $token) {
            throw new AccessDeniedHttpException();
        }

        return $user;
    }

    /**
     * @return Usr
     */
    private function getUserFromHeaders(Request $request)
    {
        /**
         * PHP does not include HTTP_AUTHORIZATION in the $_SERVER array, so this header is missing.
         * We retrieve it from apache_request_headers()
         * https://github.com/DABSquared/DABSquaredPushNotificationsBundle/blob/master/Controller/SafariController.php.
         */
        if (!$request->headers->has('Authorization') && function_exists('apache_request_headers')) {
            $all = apache_request_headers();

            if (isset($all['Authorization'])) {
                $request->headers->set('Authorization', $all['Authorization']);
            }
        }

        $auth = $request->headers->get("Authorization");
        $this->logger->warning("safari auth", ["auth" => $auth]);

        if (!preg_match('#^ApplePushNotifications (\S+)$#ims', $auth, $matches)) {
            throw new AccessDeniedHttpException();
        }

        return $this->getUserFromToken($matches[1]);
    }
}
