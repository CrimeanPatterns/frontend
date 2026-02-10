<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Loyalty\AutologinLinkValidator;
use AwardWallet\MainBundle\Service\AutologinV3Handler\AutologinV3Handler;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\AccessDenied;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\AutologinV3DisabledForProvider;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\AutologinV3IsNotAllowedForProvider;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\BrowserConnectionData;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\GetConnectionResultFailInterface;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\ImpersonatedError;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\MissingLocalPassword;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class AutologinV3Controller
{
    /**
     * @Route("/account/get-autologin-connection/{accountId}", name="aw_account_get_autologin_connection", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getConnectionAction(
        Account $account,
        AutologinV3Handler $autologinV3Handler,
        LoggerInterface $logger,
        Request $request,
        AutologinLinkValidator $autologinLinkValidator
    ) {
        $targetUrl = $request->query->get('targetURL');

        if ($targetUrl !== null && !$autologinLinkValidator->validateLink($targetUrl, $request->query->get('signature', ''))) {
            $logger->warning('Invalid targetUrl link signature: ' . $targetUrl);
            $targetUrl = null;
        }

        $result = $autologinV3Handler->getConnection($account, $targetUrl);
        $logger->info('Got autologin v3 handler result', ['result_class_string' => \get_class($result)]);

        if ($result instanceof BrowserConnectionData) {
            return new JsonResponse([
                "browserExtensionSessionId" => $result->getSessionId(),
                "browserExtensionConnectionToken" => $result->getToken(),
            ]);
        }

        if ($result instanceof GetConnectionResultFailInterface) {
            if ($result instanceof AccessDenied) {
                $error = "Access denied";

                if ($result instanceof ImpersonatedError) {
                    $error = 'You are impersonated as <b>' . $result->getImpersonatedAs()->getUsername() . '</b>. You can`t use this feature.<br>Request password access to <b>' . $account->getAccountid() . '</b> to check this account.';
                }

                return new JsonResponse(['error' => $error], 403);
            }

            if ($result instanceof AutologinV3DisabledForProvider) {
                return new JsonResponse(['error' => 'autologin v3 is not enabled for this provider'], 400);
            }

            if ($result instanceof AutologinV3IsNotAllowedForProvider) {
                return new JsonResponse(['error' => 'autologin v3 is not allowed for this provider'], 400);
            }

            if ($result instanceof MissingLocalPassword) {
                return new JsonResponse(['askLocalPassword' => true, 'error' => ACCOUNT_MISSING_PASSWORD_MESSAGE]);
            }

            return new JsonResponse(['error' => 'autologin v3 error'], 400);
        }

        throw new \LogicException('Unknown result type:' . \get_class($result));
    }
}
