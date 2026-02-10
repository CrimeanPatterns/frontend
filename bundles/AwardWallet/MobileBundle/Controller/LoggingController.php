<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class LoggingController extends AbstractController
{
    private static $verboseModules = [
        'inapppurchase',
        'extension',
    ];

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/log", name="awm_new_log", methods={"POST"})
     */
    public function logAction(Request $request, LoggerInterface $logger, AwTokenStorageInterface $tokenStorage)
    {
        $a = 1;
        $logData = JsonRequestHandler::parse($request);
        $module = $logData['module'] ?? null;
        //        $this->get('logger')->warning($request->getContent());
        $logContext = [
            '_aw_mobile' => 1,
            '_aw_userid' => null,
            '_aw_login' => null,
        ];

        if (null !== $tokenStorage->getUser()) {
            $token = $tokenStorage->getToken();

            if (isset($token)) {
                $user = $token->getUser();

                if ($user instanceof Usr) {
                    $logContext['_aw_userid'] = $logData['userid'] = $user->getUserid();
                    $logContext['_aw_login'] = $logData['login'] = $user->getLogin();
                }
            }
        }

        if (isset($logData['data']['stack'])) {
            $logData['data']['stack'] = self::filterValue($logData['data']['stack'],
                (!isset($logData['data']['message']) || (strpos($logData['data']['message'], 'http://errors.angularjs.org') !== false)) ?
                    /* limit huge angular traces */ 250 : 1000
            );
        }

        if (isset($logData)) {
            if (isset($logData['module'])) {
                $logContext['_aw_mobile_client_module'] = $logData['module'];
            }

            if (isset($logData['appVersion'])) {
                $logContext['_aw_mobile_version'] = $logData['appVersion'];
            }

            //            if (isset($logData['data']['message'])) {
            //                $logContext['_aw_mobile_message'] = $logData['data']['message'];
            //            }
        }

        $logger->warning(json_encode($logData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE), $logContext);

        return new JsonResponse(['success' => true]);
    }

    private static function doNotSendEmail($logData)
    {
        if (!$logData) {
            return true;
        }

        $message = $logData['data']['message'] ?? '';

        if (
            stripos($message, '$digest() iterations reached') !== false
            || stripos($message, '$digest already in progress') !== false
            || stripos($message, '__gCrWeb.autofill.extractForms') !== false
            || stripos($message, 'InvalidStateError: SockJS has already been closed') !== false
        ) {
            return true;
        }

        $module = strtolower($logData['module'] ?? '');

        if ('' === $module) {
            return false;
        }

        foreach (self::$verboseModules as $verboseModule) {
            if (strpos($module, $verboseModule) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function filterValue($value, $maxLength = 100)
    {
        if (is_string($value)) {
            $value = substr($value, 0, $maxLength);
        } elseif (is_array($value)) {
            $budget = $maxLength;
            $filtered = [];

            foreach ($value as $key => $part) {
                $printrOfPart = json_encode($part, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $printrOfPartLength = strlen($printrOfPart);

                if ($budget - $printrOfPartLength > 0) {
                    $filtered[$key] = $part;
                } else {
                    break;
                }

                $budget -= $printrOfPartLength;

                if ($budget <= 0) {
                    break;
                }
            }

            $value = $filtered;
        }

        return $value;
    }
}
