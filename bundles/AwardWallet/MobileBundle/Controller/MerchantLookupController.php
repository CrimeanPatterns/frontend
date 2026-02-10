<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\AwSecureToken;
use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\OfferQuery;
use AwardWallet\MainBundle\Service\MerchantLookupHandler;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/account/merchants")
 */
class MerchantLookupController extends AbstractController
{
    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/data", name="awm_merchant_lookup_data", methods={"POST"})
     * @Security("is_granted('CSRF')")
     * @AwSecureToken(
     *     service="aw.mobile_token_checker",
     *     lifetime=60,
     *     methods={"POST"}
     * )
     * @JsonDecode
     */
    public function getMerchantData(Request $request, MerchantLookupHandler $merchantLookupHandlerReplica): JsonResponse
    {
        $session = $request->getSession();

        if ($session && $session->isStarted()) {
            $session->save();
        }

        return $merchantLookupHandlerReplica->handleMerchantDataRequest($request);
    }

    /**
     * @Route("/offer/{merchantId}",
     *     name="awm_spent_analysis_merchant_offer_by_id",
     *     methods={"GET"},
     *     requirements={"merchantId":"\d+"}
     * )
     * @Template("@AwardWalletMain/SpentAnalysis/offer.html.twig")
     */
    public function getMerchantByIdOffer(): array
    {
        throw $this->createNotFoundException();
    }

    /**
     * @Route("/offer-name/{merchantName}",
     *     name="awm_spent_analysis_merchant_offer_by_name",
     *     methods={"GET"},
     *     requirements={"merchantName"=".+"}
     * )
     * @Template("@AwardWalletMain/SpentAnalysis/offer.html.twig")
     */
    public function getMerchantByNameOffer(Request $request, string $merchantName, MerchantLookupHandler $merchantLookupHandlerReplica): array
    {
        $session = $request->getSession();

        if ($session && $session->isStarted()) {
            $session->save();
        }

        $result = $merchantLookupHandlerReplica->handleExactMatchRequest(
            $request,
            $merchantName,
            OfferQuery::SOURCE_MOBILE_MCC
        );

        if (null === $result) {
            throw $this->createNotFoundException();
        }

        return $result;
    }
}
