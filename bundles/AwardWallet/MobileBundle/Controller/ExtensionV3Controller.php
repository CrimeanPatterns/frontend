<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\FrameworkExtension\Listeners\CustomHeadersListener;
use AwardWallet\MainBundle\Loyalty\ApiCommunicator;
use AwardWallet\MainBundle\Service\AutologinV3Handler\AutologinV3Handler;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\BrowserConnectionData;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\GetConnectionResultFailInterface;
use AwardWallet\MainBundle\Service\AutologinV3Handler\Result\MissingLocalPassword;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/extension-v3")
 */
class ExtensionV3Controller
{
    /**
     * @Route("/start")
     * @Template("@AwardWalletMobile/Account/Extension/extension_v3.html.twig")
     */
    public function extensionV3Action(Request $request, CustomHeadersListener $customHeadersListener)
    {
        return [];
    }

    /**i
     * @Route("/response", name="extension_response_mobile", methods={"POST"})
     */
    public function extensionResponseAction(Request $request, ApiCommunicator $apiCommunicator): JsonResponse
    {
        $apiCommunicator->ExtensionResponse($request->getContent());

        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/autologin/{accountId}", name="awm_account_get_autologin_connection", methods={"POST"})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     */
    public function autologin(
        Account $account,
        AutologinV3Handler $autologinV3Handler,
        LoggerInterface $logger
    ) {
        $result = $autologinV3Handler->getConnection($account);
        $logger->info('Got autologin v3 handler result', ['result_class_string' => \get_class($result)]);

        if ($result instanceof BrowserConnectionData) {
            return new JsonResponse([
                "browserExtensionSessionId" => $result->getSessionId(),
                "browserExtensionConnectionToken" => $result->getToken(),
            ]);
        }

        if ($result instanceof GetConnectionResultFailInterface) {
            if ($result instanceof MissingLocalPassword) {
                return new JsonResponse(['localPassword' => true]);
            }

            return new JsonResponse(['error' => 'autologin v3 error'], 400);
        }

        throw new \LogicException('Unknown result type:' . \get_class($result));
    }
}
