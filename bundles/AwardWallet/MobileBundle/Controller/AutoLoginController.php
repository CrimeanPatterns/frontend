<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Security\Reauthentication\Mobile\MobileReauthenticatorHandler;
use AwardWallet\MainBundle\Service\ExtensionStatSaver;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\AccessDenied;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\LocalPassword;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\NotFound;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\Errors\UnsupportedProvider;
use AwardWallet\MainBundle\Service\MobileExtensionHandler\MobileExtensionHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AutoLoginController extends AbstractController
{
    private ExtensionStatSaver $extensionStatSaver;

    public function __construct(
        LocalizeService $localizeService,
        ExtensionStatSaver $extensionStatSaver
    ) {
        $localizeService->setRegionalSettings();
        $this->extensionStatSaver = $extensionStatSaver;
    }

    /**
     * @Route("/account/autologin/{version}/{accountId}/{fromPhonegap}/{extension}",
     *      name="awm_newapp_autologin_autologin",
     *      defaults={"fromPhonegap" = 0, "extension" = 0},
     *      requirements={
     * 			"accountId" = "\d+",
     *			"version" = "mobile|desktop",
     * 			"fromPhonegap" = "\d+",
     * 			"extension" = "\d"
     * 		}
     * )
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF')")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function autologinLegacyAction(Account $account, $version, $fromPhonegap, $extension, MobileExtensionHandler $mobileExtensionHandler): Response
    {
        if (!$extension) {
            throw $this->createNotFoundException();
        }

        [$extensionContent, $failReason] = $mobileExtensionHandler->loadExtensionForAccount($account, $version);

        if (isset($extensionContent)) {
            return new Response($extensionContent, 200, ['Content-Type' => 'text/javascript']);
        } else {
            return $this->handleAccountAutologinFailReason($failReason);
        }
    }

    /**
     * @Route("/autologin", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function autologinComplexAction(Request $request, MobileExtensionHandler $mobileExtensionHandler, MobileReauthenticatorHandler $mobileReauthenticatorHandler, ApiVersioningService $apiVersioning): Response
    {
        $requestData = $request->request->all();

        if (
            isset($requestData['type'])
            && \is_string($requestData['type'])
        ) {
            if (
                (MobileExtensionHandler::EXTENSION_TYPE === $requestData['type'])
                && \is_string($requestData['providerCode'] ?? null)
            ) {
                [$extensionContent, $failReason] = $mobileExtensionHandler->loadExtensionForProviderByCode($requestData['providerCode']);

                if (isset($extensionContent)) {
                    return new Response($extensionContent, 200, ['Content-Type' => 'text/javascript']);
                } else {
                    return $this->handleAccountAutologinFailReason($failReason);
                }
            } elseif (
                isset($requestData['accountId'])
                && \is_numeric($requestData['accountId'])
            ) {
                [$extensionContent, $failReason] = $mobileExtensionHandler->loadExtensionForAccountById(
                    (int) $requestData['accountId'],
                    $requestData['type']
                );

                if (isset($extensionContent)) {
                    if (
                        isset($requestData['action'])
                        && StringUtils::isNotEmpty($platform = self::detectPlatformFromAction($requestData['action']))
                    ) {
                        $this->extensionStatSaver->saveExtensionHitByAccountId(
                            $platform,
                            (int) $requestData['accountId']
                        );
                    }

                    return new Response($extensionContent, 200, ['Content-Type' => 'text/javascript']);
                } else {
                    return $this->handleAccountAutologinFailReason($failReason);
                }
            } elseif (
                isset($requestData['itineraryId'])
                && \is_string($requestData['itineraryId'])
            ) {
                [$extensionContent, $failReason] = $mobileExtensionHandler->loadExtensionForItineraryById(
                    $requestData['itineraryId'],
                    $requestData['type']
                );

                if (isset($extensionContent)) {
                    if (
                        isset($requestData['action'])
                        && StringUtils::isNotEmpty($platform = self::detectPlatformFromAction($requestData['action']))
                    ) {
                        $this->extensionStatSaver->saveExtensionHitByItineraryId(
                            $platform,
                            $requestData['itineraryId']
                        );
                    }

                    return new Response($extensionContent, 200, ['Content-Type' => 'text/javascript']);
                } else {
                    return $this->handleAccountAutologinFailReason($failReason);
                }
            }
        }

        throw $this->createNotFoundException();
    }

    /**
     * @Route("/account/autologin/extension/{providerCode}",
     * 		name="awm_newapp_autologin_extension"
     * )
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"mapping" = {"providerCode" = "code"}})
     */
    public function extensionAction(Entity\Provider $provider, MobileExtensionHandler $mobileExtensionHandler): Response
    {
        [$content, $failReason] = $mobileExtensionHandler->loadExtensionForProvider($provider);

        if (isset($content)) {
            return new Response($content, 200, ['Content-Type' => 'text/javascript']);
        } else {
            return $this->handleAccountAutologinFailReason($failReason);
        }
    }

    protected function handleAccountAutologinFailReason(object $failReason): Response
    {
        switch (true) {
            case $failReason instanceof LocalPassword:
                return new JsonResponse([
                    'localPassword' => true,
                    'accountId' => $failReason->getAccount()->getAccountid(),
                ]);

            case $failReason instanceof NotFound:
            case $failReason instanceof AccessDenied:
                throw $this->createNotFoundException();

            case $failReason instanceof UnsupportedProvider:
                throw $this->createAccessDeniedException($failReason->getError());

            default:
                throw new \LogicException('Unknown fail reason');
        }
    }

    private static function detectPlatformFromAction(string $action): ?string
    {
        switch ($action) {
            case 'autologin': return Entity\Extensionstat::PLATFORM_MOBILE_AUTOLOGIN;

            case 'update': return Entity\Extensionstat::PLATFORM_MOBILE_UPDATE;
        }

        return null;
    }
}
