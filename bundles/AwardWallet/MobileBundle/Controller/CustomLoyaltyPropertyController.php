<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\LoyaltyProgramInterface;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\CustomLoyaltyPropertyManager;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @Route("/customLoyaltyProperty")
 */
class CustomLoyaltyPropertyController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private CustomLoyaltyPropertyManager $customLoyaltyPropertyManager;

    public function __construct(
        LocalizeService $localizeService,
        CustomLoyaltyPropertyManager $awManagerCustomLoyaltyProperty
    ) {
        $localizeService->setRegionalSettings();
        $this->customLoyaltyPropertyManager = $awManagerCustomLoyaltyProperty;
    }

    /**
     * @Route("/account/{accountId}",
     *     name="awm_custom_loyalty_property_account_handle",
     *     methods={"POST", "PUT", "DELETE"},
     *     requirements = {
     *         "accountId" = "\d+",
     *     }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function handleAccountAction(Request $request, Account $account)
    {
        return $this->handleRequest($request, $account);
    }

    /**
     * @Route("/account/{accountId}/{subAccountId}",
     *     name="awm_custom_loyalty_property_subaccount_handle",
     *     methods={"POST", "PUT", "DELETE"},
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
    public function handleSubAccountAction(Request $request, Account $account, Subaccount $subaccount)
    {
        return $this->handleRequest($request, $subaccount);
    }

    /**
     * @Route("/coupon/{couponId}",
     *     name="awm_custom_loyalty_property_coupon_handle",
     *     methods={"POST", "PUT", "DELETE"},
     *     requirements = {
     *         "couponId" = "\d+",
     *     }
     * )
     * @Security("is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @JsonDecode
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     */
    public function handleCouponAction(Request $request, Providercoupon $coupon)
    {
        return $this->handleRequest($request, $coupon);
    }

    protected function handleRequest(Request $request, LoyaltyProgramInterface $loyaltyProgram)
    {
        $data = $request->request->all();

        try {
            switch ($request->getMethod()) {
                case 'PUT':
                case 'POST':
                    $this->customLoyaltyPropertyManager->update($loyaltyProgram, $data);

                    break;

                case 'DELETE':
                    $this->customLoyaltyPropertyManager->delete($loyaltyProgram, $data);

                    break;
            }
        } catch (AccessDeniedException $e) {
            throw $this->createNotFoundException();
        } catch (\InvalidArgumentException $e) {
            return $this->errorJsonResponse('Invalid data');
        }

        return $this->jsonResponse(['success' => true]);
    }
}
