<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\LoggerContext\Context;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Globals\Utils\BinaryLogger\BinaryLoggerFactory;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticationRequestListener;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/push")
 */
class PushNotificationsController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/register", name="awm_newapp_push_register", methods={"POST"})
     * @JsonDecode
     * @Security("is_granted('NOT_USER_IMPERSONATED')")
     * @return JsonResponse
     */
    public function registerAction(
        Request $request,
        MobileDeviceManager $mobileDeviceManager,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        ApiVersioningService $apiVersioningService
    ) {
        $deviceKey = $request->get('id');
        $logger->debug('Push register try: ' . $deviceKey);
        $securityLogger = (new ContextAwareLoggerWrapper($logger))
            ->pushContext([Context::SERVER_MODULE_KEY => 'push_notifications'])
            ->setMessagePrefix('push register: ');
        $checkThat = (new BinaryLoggerFactory($securityLogger))->toInfo();

        $log = function (string $log, array $context = []) use ($securityLogger) {
            $securityLogger->info($log, $context);
        };
        $type = $request->get('type');

        if (!isset($type)) {
            return new JsonResponse(['success' => false], 400);
        }

        $user = $this->getCurrentUser();
        $tracked = true;
        $deviceRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\MobileDevice::class);

        if (StringUtils::isEmpty($deviceKey)) {
            if ($request->headers->has(MobileHeaders::MOBILE_DEVICE_ID)) {
                $log('device id header provided', ['mobile_device_id' => $request->headers->get(MobileHeaders::MOBILE_DEVICE_ID)]);
                $deviceById = $deviceRep->findOneBy(['userId' => $user, 'mobileDeviceId' => $request->headers->get(MobileHeaders::MOBILE_DEVICE_ID)]);

                if ($deviceById) {
                    $log('device found by id, using existing device key', ['mobile_device_by_id' => $deviceById->getMobileDeviceId()]);
                    $deviceKey = $deviceById->getDeviceKey();
                } else {
                    $log('device not found by id');
                }
            }

            if (StringUtils::isEmpty($deviceKey)) {
                $log('no device key provided, untracked device, generating a new key');
                $deviceKey = StringUtils::getPseudoRandomString(512);
            }

            $tracked = false;
        } else {
            $log('device key provided, tracked device');

            if ($request->headers->has(MobileHeaders::MOBILE_DEVICE_ID)) {
                $log('device id header provided', ['mobile_device_id' => $request->headers->get(MobileHeaders::MOBILE_DEVICE_ID)]);
                $deviceById = $deviceRep->findOneBy(['userId' => $user, 'mobileDeviceId' => $request->headers->get(MobileHeaders::MOBILE_DEVICE_ID)]);

                if ($deviceById) {
                    $log('device found by id', ['mobile_device_by_id' => $deviceById->getMobileDeviceId()]);
                    $deviceByKey = $deviceRep->findOneBy(['userId' => $user, 'deviceKey' => $deviceKey]);

                    if (
                        $deviceByKey
                        && ($deviceByKey->getMobileDeviceId() !== $deviceById->getMobileDeviceId())
                    ) {
                        $log(
                            'device found by key, differs from device found by id, removing device found by key',
                            [
                                'mobile_device_by_id' => $deviceById->getMobileDeviceId(),
                                'mobile_device_by_key' => $deviceByKey->getMobileDeviceId(),
                            ]
                        );
                        $entityManager->remove($deviceByKey);
                        $entityManager->flush();
                    } else {
                        $log(
                            'device not found by key or found the same as device found by id',
                            \array_merge(
                                [
                                    'mobile_device_by_id' => $deviceById->getMobileDeviceId(),
                                    'mobile_device_by_key' => $deviceByKey ? $deviceByKey->getMobileDeviceId() : null,
                                ]
                            )
                        );
                    }

                    $log('updating device key for device found by id', ['mobile_device_by_id' => $deviceById->getMobileDeviceId()]);
                    $deviceById->setDeviceKey($deviceKey);
                    $entityManager->flush();
                }
            }
        }

        $registeredDevice = $mobileDeviceManager->addDevice(
            $user->getUserid(),
            $type,
            $deviceKey,
            $apiVersioningService->supports(MobileVersions::REGIONAL_SETTINGS) ?
                $user->getLanguage() :
                $request->getLocale(),
            $request->headers->get(MobileHeaders::MOBILE_VERSION),
            $request->getClientIp(),
            $request->getSession(),
            $tracked
        );

        if (
            $checkThat('device')->was('registered')
            ->on(
                $registeredDevice,
                $registeredDevice ? ['mobile_device_id' => $registeredDevice->getMobileDeviceId()] : []
            )
        ) {
            if (
                $checkThat('registered device')->hasNot('a secret in db')
                    ->on(!$registeredDevice->hasSecret())
                && $checkThat('session')->has('enable keychain flag')
                    ->on($request->getSession()->get(MobileReauthenticationRequestListener::SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY))
                && $checkThat('device version')->does('support keychain reauth')
                    ->on($apiVersioningService->supports(MobileVersions::KEYCHAIN_REAUTH))
            ) {
                $secret = $mobileDeviceManager->generateKeychainForCurrentDevice();

                if (isset($secret)) {
                    $log('will send keychain reauth token');
                    $request->getSession()->remove(MobileReauthenticationRequestListener::SESSION_ENABLE_KEYCHAIN_AFTER_LOGGING_IN_KEY);
                    $request->attributes->set(
                        MobileReauthenticationRequestListener::REQUEST_KEYCHAIN_ATTRIBUTE,
                        $secret
                    );
                } else {
                    $log('will not send keychain reauth token, secret was not generated');
                }
            }

            return new JsonResponse(['success' => true, 'deviceId' => $registeredDevice->getMobileDeviceId()]);
        } else {
            return new JsonResponse(['success' => false]);
        }
    }

    /**
     * @Route("/unregister", name="awm_newapp_push_unregister", methods={"POST"})
     * @JsonDecode
     * @Security("is_granted('NOT_USER_IMPERSONATED')")
     * @return JsonResponse
     */
    public function unregisterAction(Request $request, MobileDeviceManager $awManagerMobileDeviceManager)
    {
        $id = $request->get('id');
        $type = $request->get('type');

        if (!isset($id) || !isset($type)) {
            return new JsonResponse(['success' => false]);
        }
        $awManagerMobileDeviceManager->removeDeviceByKey($id, $type);

        return new JsonResponse(['success' => true]);
    }
}
