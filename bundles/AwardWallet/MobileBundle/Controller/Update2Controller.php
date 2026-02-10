<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Updater\EventsChannelMigrator;
use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use AwardWallet\MainBundle\Updater\RequestHandler;
use AwardWallet\MainBundle\Updater\UpdaterSession;
use AwardWallet\MainBundle\Updater\UpdaterSessionFactory;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use AwardWallet\MainBundle\Updater\UpdaterStateException;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/account/update2")
 */
class Update2Controller extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("", name="awm_newapp_account_updater_start", methods={"POST"})
     * @return JsonResponse
     */
    public function startAction(
        Request $request,
        AwTokenStorageInterface $awTokenStorage,
        TranslatorHijacker $translatorHijacker,
        ApiVersioningService $apiVersioningService,
        LoggerInterface $loggerStat,
        UpdaterSessionManager $updaterSessionManager,
        UpdaterSession $awUpdaterSessionMobile
    ) {
        $translatorHijacker->setContext('mobile');
        $requestData = JsonRequestHandler::parse($request);

        if (!(isset($requestData['accounts']) && is_array($requestData['accounts']) && !empty($requestData['accounts']))) {
            throw $this->createNotFoundException();
        }

        if (empty($requestData['startKey'])) {
            throw $this->createNotFoundException();
        }

        $options = [
            Option::BROWSER_SUPPORTED => true,
            Option::EXTENSION_INSTALLED => $apiVersioningService->supports(MobileVersions::NATIVE_APP),
            Option::EXTRA => [
                'add' => $this->getCurrentUser()->getItineraryadddate(),
                'update' => $this->getCurrentUser()->getItineraryupdatedate(),
            ],
            Option::PLATFORM => UpdaterEngineInterface::SOURCE_MOBILE,
            Option::CLIENT_PLATFORM => ClientPlatform::MOBILE,
            Option::EXTENSION_V3_SUPPORTED => $requestData['extensionV3Supported'] ?? false,
            Option::EXTENSION_V3_INSTALLED => $requestData['extensionV3Supported'] ?? false,
        ];

        $user = $awTokenStorage->getBusinessUser();

        try {
            if ($user->isUpdater3k() && $apiVersioningService->supports(MobileVersions::UPDATER_ASYNC_EVENTS)) {
                $requestData['client'] = 'test_me';
                $loggerStat->info('updater start key: ' . ((int) $requestData['startKey']));

                $ret = $updaterSessionManager->startSessionLockSafe(
                    (int) $requestData['startKey'],
                    $requestData['client'],
                    UpdaterSessionFactory::TYPE_MOBILE,
                    $options,
                    $requestData['accounts'],
                    $request
                );
            } else {
                $ret = $awUpdaterSessionMobile->startLockSafe(
                    $user,
                    (int) $requestData['startKey'],
                    $requestData['accounts'],
                    $options
                );
            }
        } catch (UpdaterStateException $e) {
            $loggerStat->info('mobile_updater', [
                'error' => 1,
                'exceptionMessage' => $e->getMessage(),
                'exceptionTrace' => $e->getTraceAsString(),
                'state' => 'start',
            ]);

            throw new BadRequestHttpException('Updater error.');
        } catch (LockConflictedException $e) {
            $loggerStat->info('mobile_updater', [
                'error' => 1,
                'exceptionMessage' => $e->getMessage(),
                'exceptionTrace' => $e->getTraceAsString(),
                'state' => 'start',
            ]);

            throw new TooManyRequestsHttpException(5);
        }

        $loggerStat->info('mobile_updater', [
            'state' => 'start',
            'accounts' => $requestData['accounts'],
            'startKey' => $ret->startKey,
            'key' => $ret->key,
            'eventsCount' => count($ret->events),
        ]);

        return $this->jsonResponse($ret);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @Route("/{key}/{eventIndex}",
     *      name = "awm_newapp_account_updater_progress",
     *      methods={"POST"},
     *      requirements = {
     *          "key": "[a-z]+",
     *          "eventIndex" : "\d+"
     *      },
     *      defaults = {
     *          "eventIndex" : null
     *      }
     * )
     */
    public function progressAction(
        string $key,
        ?string $eventIndex,
        Request $request,
        AwTokenStorageInterface $awTokenStorage,
        TranslatorHijacker $translatorHijacker,
        TranslatorInterface $translator,
        LoggerInterface $loggerStat,
        ApiVersioningService $apiVersioningService,
        RequestHandler $requestHandler,
        UpdaterSession $awUpdaterSessionMobile
    ): JsonResponse {
        $translatorHijacker->setContext('mobile');
        $translator->setDumpKeysEnabled(false);
        $user = $awTokenStorage->getBusinessUser();

        if ($user->isUpdater3k() && $apiVersioningService->supports(MobileVersions::UPDATER_ASYNC_EVENTS)) {
            $requestHandler->handleAsyncProgress($request, $key);

            return $this->successJsonResponse();
        } else {
            $result = $requestHandler
                ->setUpdater($awUpdaterSessionMobile)
                ->handleProgress($request, $key, $eventIndex);

            $loggerStat->info('mobile_updater', [
                'state' => 'progress',
                'key' => $key,
                'eventIndex' => $eventIndex,
                'eventsCount' => count($result),
            ]);

            return $this->jsonResponse((object) $result);
        }
    }

    /**
     * @Route("/migrate-events-channel", methods={"POST"})
     * @JsonDecode()
     */
    public function migrateEventsChannel(
        Request $request,
        EventsChannelMigrator $eventsChannelMigrator,
        AntiBruteforceLockerService $awSecurityAntibruteforceCentrifugeChannelAuth
    ): JsonResponse {
        $token = $request->get('token');

        if (!\is_string($token)) {
            throw new BadRequestHttpException('Invalid token');
        }

        if (null !== $awSecurityAntibruteforceCentrifugeChannelAuth->checkForLockout($request->getClientIp())) {
            return new JsonResponse([
                'success' => false,
            ]);
        }

        $session = $request->getSession();

        if (!$session->isStarted()) {
            $session->start();
        }

        return new JsonResponse([
            'success' => $eventsChannelMigrator->receive($token, $request)]
        );
    }
}
