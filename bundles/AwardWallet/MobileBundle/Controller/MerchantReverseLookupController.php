<?php

declare(strict_types=1);

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Form\Type\Mobile\Merchant\MerchantReverseLookupType;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\MerchantLookup;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/merchant-reverse")
 */
class MerchantReverseLookupController extends AbstractController
{
    use JsonTrait;

    public function __construct(
        LocalizeService $localizeService
    ) {
        $localizeService->setRegionalSettings();
    }

    /**
     * @Route("/data", name="awm_merchant_reverse_lookup_data", methods={"GET"})
     * @Security("is_granted('CSRF')")
     */
    public function dataAction(MerchantLookup $merchantLookup): JsonResponse
    {
        return $this->jsonResponse($merchantLookup->getReverseLookupInitial());
    }

    /**
     * @Route("/form", name="awm_merchant_reverse_lookup_form", methods={"GET"})
     * @Security("is_granted('CSRF')")
     */
    public function formAction(MerchantLookup $merchantLookup, FormFactoryInterface $formFactory, FormDehydrator $formDehydrator)
    {
        $form = $formFactory->create(MerchantReverseLookupType::class, $merchantLookup->getReverseLookupInitial());

        return $this->jsonResponse($formDehydrator->dehydrateForm($form));
    }

    /**
     * @Route("/offer/{id}",
     *     name="awm_merchant_reverse_lookup_offer",
     *     methods={"GET"},
     *     requirements={"id" = "\d+"}
     * )
     */
    public function offerAction(string $id): Response
    {
        return $this->forward('AwardWallet\MainBundle\Controller\MerchantReverseLookupController::offerAction', ['id' => $id]);
    }
}
