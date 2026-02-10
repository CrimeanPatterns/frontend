<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Updater\ExtensionV3SessionMap;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ExtensionV3Controller
{
    /**
     * it is anonymous, because mobile app launching browser without session.
     *
     * @Route("/extension-response", name="extension_response", methods={"POST"})
     */
    public function extensionResponseAction(Request $request, LoggerInterface $logger, ApiCommunicator $apiCommunicator): Response
    {
        $response = $request->getContent();

        if (strlen($response) > (64 * 1024 * 1024)) {
            $logger->warning("extension response is too long");

            return new JsonResponse("ok");
        }

        $apiCommunicator->ExtensionResponse($response);

        return new JsonResponse("ok");
    }

    /**
     * it is anonymous, because mobile app launching browser without session.
     *
     * @Route("/extension-save-login-id", name="extension_save_login_id", methods={"POST"})
     */
    public function extensionSaveLoginIdAction(Request $request, LoggerInterface $logger, ExtensionV3SessionMap $sessionMap, Connection $connection): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['loginId'], $data['login'], $data['sessionId'])) {
            return new JsonResponse("error", 400);
        }

        if (!is_string($data['loginId']) || !is_string($data['login']) || !is_string($data['sessionId'])) {
            return new JsonResponse("error", 400);
        }

        $accountId = $sessionMap->getAccountId($data['sessionId']);

        if ($accountId === null) {
            $logger->info(__FUNCTION__ . ': account not found, returning 200 anyway to prevent session from being bruteforced');

            return new JsonResponse("ok", 200);
        }

        $affected = $connection->update("Account", ["LoginID" => $data['loginId'] . ":" . $data['login']], ["AccountID" => $accountId, "Login" => $data['login']]);
        $logger->info("login id saved, accountId: $accountId, loginId: " . $data['loginId'] . ", login: " . $data['login'] . ", affected: $affected");

        return new JsonResponse("ok");
    }
}
