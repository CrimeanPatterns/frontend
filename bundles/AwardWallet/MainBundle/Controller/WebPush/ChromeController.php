<?php

namespace AwardWallet\MainBundle\Controller\WebPush;

use AwardWallet\MainBundle\Entity\MobileDevice;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ChromeController extends AbstractController
{
    /**
     * @Route("/webPush/chrome/subscribe", name="aw_push_chrome_subscribe", options={"expose": true})
     * @return JsonResponse - true to resubscribe
     */
    public function subscribeAction(
        Request $request,
        LoggerInterface $logger,
        AuthorizationCheckerInterface $authorizationChecker,
        MobileDeviceManager $mobileDeviceManager
    ) {
        $endpoint = $request->request->get('endpoint');
        $key = $request->request->get('key');
        $token = $request->request->get('token');
        $vapid = $request->request->get('vapid', 'false') === 'true';

        if (empty($endpoint) || filter_var($endpoint, FILTER_VALIDATE_URL) === false || empty($key) || empty($token)) {
            throw new BadRequestHttpException();
        }

        if ($authorizationChecker->isGranted('USER_IMPERSONATED') || $authorizationChecker->isGranted('USER_IMPERSONATED_AS_SUPER')) {
            return new JsonResponse(false);
        }

        $userAgent = $request->headers->get('user-agent');

        $mobileDeviceManager->addDevice(
            $userId = empty($this->getUser()) ? null : $this->getUser()->getUserid(),
            MobileDevice::getTypeName(
                (false !== strpos($userAgent, 'Firefox')) ?
                    MobileDevice::TYPE_FIREFOX :
                    MobileDevice::TYPE_CHROME
            ),
            json_encode(["endpoint" => $endpoint, 'key' => $key, 'token' => $token, 'vapid' => $vapid]),
            $request->getLocale(),
            'web:' . ($authorizationChecker->isGranted('SITE_BUSINESS_AREA') ? 'business' : 'personal'),
            $request->getClientIp(),
            $request->getSession(),
            true,
            $userAgent
        );
        $logger->warning('chrome device subscribe', ['userid' => $userId, "endpoint" => $endpoint, 'vapid' => $vapid]);

        return new JsonResponse(false);
    }

    /**
     * @Route("/webPush/chrome/manifest.json", name="aw_push_chrome_manifest")
     * @return Response
     */
    public function manifestAction(string $pushNotificationsAndroidSenderId)
    {
        $manifest = file_get_contents(__DIR__ . '/../../Service/WebPush/manifest.json');
        $manifest = str_ireplace('%GCM_SENDER_ID%', $pushNotificationsAndroidSenderId, $manifest);

        return new Response($manifest, 200, ['Content-Type' => 'application/json']);
    }
}
