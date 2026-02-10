<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\AccountHistory\Formatter\MobileMileValueFormatter;
use AwardWallet\MainBundle\Service\MileValue\MileValueService;
use AwardWallet\MainBundle\Service\MileValue\UserPointValueService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/mile-value")
 */
class MileValueController extends AbstractController
{
    private MileValueService $mileValueService;
    private MobileMileValueFormatter $mobileMileValueFormatter;
    private AwTokenStorageInterface $tokenStorage;
    private LocalizeService $localizer;

    public function __construct(
        MileValueService $mileValueService,
        MobileMileValueFormatter $mobileMileValueFormatter,
        AwTokenStorageInterface $tokenStorage,
        LocalizeService $localizer
    ) {
        $this->mileValueService = $mileValueService;
        $this->mobileMileValueFormatter = $mobileMileValueFormatter;
        $this->tokenStorage = $tokenStorage;
        $this->localizer = $localizer;
    }

    /**
     * @Route("/data",
     *     name="awm_mile_value_data",
     *     methods={"POST"}
     * )
     * @IsGranted("CSRF")
     */
    public function dataAction(): JsonResponse
    {
        return $this->json($this->mobileMileValueFormatter->formatList($this->mileValueService->getFlatDataList(false)));
    }

    /**
     * @Route("/{providerId}",
     *     name="awm_mile_value_remove",
     *     methods={"DELETE"}
     * )
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"id"="providerId"})
     * @IsGranted("CSRF")
     */
    public function deleteAction(Provider $provider, UserPointValueService $userPointValueService): JsonResponse
    {
        $userId = $this->tokenStorage->getBusinessUser()->getId();
        $isSuccess = $userPointValueService->removeProviderUserPointValue($userId, $provider->getId());

        // hack for KLM (Flying Blue) / Air France (Flying Blue)
        if (Provider::KLM_ID === $provider->getId()) {
            $isSuccess = $userPointValueService->removeProviderUserPointValue($userId, Provider::AIRFRANCE_ID);
        } elseif (Provider::AIRFRANCE_ID === $provider->getId()) {
            $isSuccess = $userPointValueService->removeProviderUserPointValue($userId, Provider::KLM_ID);
        }

        return $this->json(['success' => $isSuccess]);
    }

    /**
     * @Route("/{providerId}",
     *     name="awm_mile_value_set",
     *     methods={"POST"}
     * )
     * @JsonDecode
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"id"="providerId"})
     * @IsGranted("CSRF")
     */
    public function setAction(
        Request $request,
        Provider $provider,
        UserPointValueService $userPointValueService,
        TranslatorInterface $translator
    ): JsonResponse {
        $userId = $this->tokenStorage->getBusinessUser()->getId();
        $value = $request->request->get('value', 0);
        $result = ['success' => false];

        if (false === strpos($value, '.') && false !== strpos($value, ',')) {
            $value = (float) str_replace(',', '.', $value);
        }

        if (!\is_scalar($value)) {
            return $this->json(array_merge($result, ['error' => $translator->trans('invalid.symbols', [], 'validators')]));
        }

        $isSuccess = false;

        if (!\is_float($value)) {
            $formatter = \NumberFormatter::create($this->localizer->getLocale(), \NumberFormatter::DECIMAL);
            $value = (float) (in_array($formatter->getSymbol(\NumberFormatter::GROUPING_SEPARATOR_SYMBOL), [',', '.'])
                ? $formatter->parse($value, \NumberFormatter::TYPE_DOUBLE)
                : $value);
        }

        if (
            \is_float($value)
            && !\is_nan($value)
            && !\is_infinite($value)
            && MileValueService::isValidValue($value)
        ) {
            $isSuccess = $userPointValueService->setProviderUserPointValue($userId, $provider->getId(), $value);

            // hack for KLM (Flying Blue) / Air France (Flying Blue)
            if (Provider::KLM_ID === $provider->getId()) {
                $isSuccess = $userPointValueService->setProviderUserPointValue($userId, Provider::AIRFRANCE_ID, $value);
            } elseif (Provider::AIRFRANCE_ID === $provider->getId()) {
                $isSuccess = $userPointValueService->setProviderUserPointValue($userId, Provider::KLM_ID, $value);
            }
        }

        $result['success'] = $isSuccess;

        if (!$isSuccess) {
            $result['error'] = $translator->trans('u-assign-value-high', [
                '%provider%' => $provider->getDisplayname(),
                '%providerCurrency%' => $provider->getCurrency()->getName(),
                '%break%' => ' ',
            ]);
        }

        return $this->json($result);
    }
}
