<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Form\Extension\JsonFormExtension\JsonRequestHandler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Updater\Engine\UpdaterEngineInterface;
use AwardWallet\MainBundle\Updater\Option;
use AwardWallet\MainBundle\Updater\Options\ClientPlatform;
use AwardWallet\MainBundle\Updater\RequestHandler;
use AwardWallet\MainBundle\Updater\UpdaterSessionFactory;
use AwardWallet\MainBundle\Updater\UpdaterSessionManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Lock\Exception\LockConflictedException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/async-update")
 */
class AsyncUpdaterController extends AbstractController
{
    /**
     * @Security("is_granted('UPDATER_3K') and is_granted('CSRF')")
     * @Route("/start", name="aw_account_async_updater_start", methods={"POST"}, options={"expose"=true})
     */
    public function startAction(
        Request $request,
        AwTokenStorageInterface $tokenStorage,
        UpdaterSessionManager $sessionManager
    ): JsonResponse {
        $requestData = JsonRequestHandler::parse($request);

        if (!(isset($requestData['accounts']) && is_array($requestData['accounts']) && !empty($requestData['accounts']))) {
            throw new BadRequestHttpException();
        }

        if (empty($requestData['startKey'])) {
            throw new BadRequestHttpException();
        }

        if (empty($requestData['client']) || !is_string($requestData['client'])) {
            throw new BadRequestHttpException();
        }

        $options = [
            Option::BROWSER_SUPPORTED => !empty($requestData['supportedBrowser']),
            Option::EXTENSION_INSTALLED => !empty($requestData['extensionAvailable']),
            Option::EXTENSION_V3_INSTALLED => !empty($requestData['extensionV3Installed']),
            Option::EXTENSION_V3_SUPPORTED => !empty($requestData['extensionV3Supported']),
            Option::CHECK_TRIPS => !empty($requestData['trips']),
            Option::SOURCE => $requestData['source'],
            Option::EXTRA => [
                'add' => $tokenStorage->getToken()->getUser()->getItineraryadddate(),
                'update' => $tokenStorage->getToken()->getUser()->getItineraryupdatedate(),
            ],
            Option::PLATFORM => UpdaterEngineInterface::SOURCE_DESKTOP,
            Option::CLIENT_PLATFORM => ClientPlatform::DESKTOP,
        ];

        try {
            $ret = $sessionManager->startSessionLockSafe(
                (int) $requestData['startKey'],
                $requestData['client'],
                UpdaterSessionFactory::TYPE_DESKTOP,
                $options,
                $requestData['accounts'],
                $request
            );
        } catch (LockConflictedException $e) {
            throw new TooManyRequestsHttpException(5);
        }

        $response = new JsonResponse($ret);
        $response->setEncodingOptions($response->getEncodingOptions() | JSON_PRETTY_PRINT);

        return $response;
    }

    /**
     * @Security("is_granted('UPDATER_3K') and is_granted('CSRF')")
     * @Route("/progress/{key}",
     *      name = "aw_account_async_updater_progress",
     *      methods={"GET", "POST"},
     *      requirements = {
     *          "key": "[a-z]+"
     *      },
     *      options={"expose"=true}
     * )
     */
    public function progressAction(string $key, Request $request, RequestHandler $requestHandler): JsonResponse
    {
        $requestHandler->handleAsyncProgress($request, $key);

        return new JsonResponse([]);
    }
}
