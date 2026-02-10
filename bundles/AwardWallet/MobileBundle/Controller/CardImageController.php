<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\ApiVersion;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\CardImage;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\FrameworkExtension\HttpFoundation\StreamCopyResponse;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\CardImage\CardImageManager;
use AwardWallet\MainBundle\Manager\CardImage\HttpHandler\MobileHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class CardImageController.
 *
 * @Route("/cardImage")
 */
class CardImageController extends AbstractController
{
    public function __construct(LocalizeService $localizeService, TranslatorHijacker $translatorHijacker)
    {
        $localizeService->setRegionalSettings();
        $translatorHijacker->setContext('mobile');
    }

    /**
     * @Route("/", name="awm_card_image_upload", methods={"POST"})
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @ApiVersion(features={MobileVersions::CARD_IMAGES_ON_FORM}, domain="mobile")
     * @JsonDecode
     * @return JsonResponse
     */
    public function uploadAction(Request $request, SessionInterface $session, MobileHandler $cardImageHttpHandlerMobile)
    {
        $session->save();

        return $cardImageHttpHandlerMobile->handleUploadRequest($request);
    }

    /**
     * @Route("/{cardImageId}", name="awm_card_image_download", methods={"GET"})
     * @ParamConverter("cardImage", class="AwardWalletMainBundle:CardImage", options={"id": "cardImageId"})
     * @return StreamCopyResponse
     */
    public function loadAction(
        CardImage $cardImage,
        bool $responseStreaming,
        SessionInterface $session,
        CardImageManager $cardImageManager
    ) {
        if (!$this->isGranted('VIEW', $cardImage)) {
            throw $this->createNotFoundException();
        }

        $session->save();
        $imageStream = $cardImageManager->getImageStream($cardImage);

        if (!isset($imageStream)) {
            throw $this->createNotFoundException();
        }

        $expireDate = clone $cardImage->getUploadDate();
        $expireDate->modify('+10 years');

        if ($responseStreaming) {
            $response = (new StreamCopyResponse(
                $imageStream,
                $cardImage->getFileSize(),
                200,
                ['Content-Type' => $cardImage->getFormat()]
            ));
        } else {
            $response = new Response($content = (string) $imageStream, 200, ['Content-Length' => strlen($content)]);
        }

        $response
            ->setExpires($expireDate)
            ->setLastModified($cardImage->getUploadDate())
            ->setCache(['private' => true, 'max_age' => $expireDate->getTimestamp() - time()]);

        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $cardImage->getFileName()));
        $response->headers->set('Pragma', '');

        return $response;
    }

    /**
     * @Route("/account/{accountId}/{subAccountId}",
     *     name="awm_card_image_subaccount_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     requirements = {
     *         "accountId" = "\d+",
     *         "subAccountId" = "\d+",
     *     }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subaccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     */
    public function handleSubAccountAction(Request $request, Account $account, Subaccount $subaccount, MobileHandler $cardImageHttpHandlerMobile)
    {
        if (PROVIDER_KIND_CREDITCARD === $account->getKind()) {
            return $this->errorJsonResponse("Can't save credit card image");
        }

        if ($subaccount->getAccountid() !== $account) {
            throw $this->createNotFoundException();
        }

        return $cardImageHttpHandlerMobile->handleLoyaltyRequest($request, $subaccount);
    }

    /**
     * @Route("/account/{accountId}",
     *     name="awm_card_image_account_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     requirements = {
     *         "accountId" = "\d+"
     *     }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function handleAccountAction(Request $request, Account $account, MobileHandler $cardImageHttpHandlerMobile)
    {
        if (PROVIDER_KIND_CREDITCARD === $account->getKind()) {
            return $this->errorJsonResponse("Can't save credit card image");
        }

        return $cardImageHttpHandlerMobile->handleLoyaltyRequest($request, $account);
    }

    /**
     * @Route("/coupon/{couponId}",
     *     name="awm_card_image_coupon_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Route("/document/{couponId}",
     *     name="awm_card_image_document_handle",
     *     methods={"POST", "DELETE", "HEAD"},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function handleCouponAction(Request $request, Providercoupon $coupon, MobileHandler $cardImageHttpHandlerMobile)
    {
        if (PROVIDER_KIND_CREDITCARD === $coupon->getKind()) {
            return $this->errorJsonResponse("Can't save credit card image");
        }

        return $cardImageHttpHandlerMobile->handleLoyaltyRequest($request, $coupon);
    }
}
