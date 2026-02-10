<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Location;
use AwardWallet\MainBundle\Entity\LocationSetting;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileFormatter\Desanitizer;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\FormDehydrator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\ProviderTranslator;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use AwardWallet\MobileBundle\Form\Type\LoyaltyLocationType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class LocationController.
 *
 * @Route("/location")
 */
class LocationController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private AwTokenStorageInterface $awTokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private int $loyaltyLocationMax;
    private LoyaltyLocation $loyaltyLocation;
    private Handler $awFormLoyaltyLocationHandlerMobile;
    private Desanitizer $desanitizer;
    private FormDehydrator $formDehydrator;
    private TrackedLocationsLimiter $trackedLocationsLimiter;

    public function __construct(
        LocalizeService $localizeService,
        AwTokenStorageInterface $awTokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        $loyaltyLocationMax,
        TrackedLocationsLimiter $trackedLocationsLimiter,
        LoyaltyLocation $loyaltyLocation,
        Handler $awFormLoyaltyLocationHandlerMobile,
        Desanitizer $desanitizer,
        FormDehydrator $formDehydrator
    ) {
        $localizeService->setRegionalSettings();
        $this->awTokenStorage = $awTokenStorage;
        $this->loyaltyLocationMax = $loyaltyLocationMax;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->loyaltyLocation = $loyaltyLocation;
        $this->awFormLoyaltyLocationHandlerMobile = $awFormLoyaltyLocationHandlerMobile;
        $this->desanitizer = $desanitizer;
        $this->formDehydrator = $formDehydrator;
        $this->trackedLocationsLimiter = $trackedLocationsLimiter;
    }

    /**
     * @Route("/account/{accountId}/{subAccountId}/add",
     *     name="aw_mobile_subaccount_location_add",
     *     methods={"GET", "PUT"},
     *     requirements={
     *         "accountId" = "\d+",
     *         "subAccountId" = "\d+"
     *     }
     * )
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @ParamConverter("subaccount", class="AwardWalletMainBundle:Subaccount", options={"id" = "subAccountId"})
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', account)")
     * @JsonDecode
     */
    public function addToSubaccountAction(
        Request $request,
        Account $account,
        Subaccount $subaccount,
        ProviderTranslator $providerTranslator
    ) {
        $displayName = $account->getProviderid() ?
            $providerTranslator->translateDisplayNameByEntity($account->getProviderid()) :
            $account->getProgramname();

        $kind = $account->getProviderid() ? $account->getProviderid()->getKind() : $account->getKind();

        return $this->handleAddRequest(
            $request,
            (new Location())->setContainer($subaccount),
            $kind,
            $displayName,
            $account->getLogin()
        );
    }

    /**
     * @Route("/account/{accountId}/add",
     *     name="aw_mobile_account_location_add",
     *     methods={"GET", "PUT"},
     *     requirements={
     *         "accountId" = "\d+"
     *     }
     * )
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', account)")
     * @JsonDecode
     */
    public function addToAccountAction(Request $request, Account $account, ProviderTranslator $providerTranslator)
    {
        $displayName = $account->getProviderid() ?
            $providerTranslator->translateDisplayNameByEntity($account->getProviderid()) :
            $account->getProgramname();

        $kind = $account->getProviderid() ? $account->getProviderid()->getKind() : $account->getKind();

        return $this->handleAddRequest(
            $request,
            (new Location())->setContainer($account),
            $kind,
            $displayName,
            $account->getLogin()
        );
    }

    /**
     * @Route("/coupon/{couponId}/add",
     *     name="aw_mobile_coupon_location_add",
     *     methods={"GET", "PUT"},
     *     requirements={
     *         "couponId" = "\d+"
     *     }
     * )
     * @ParamConverter("coupon", class="AwardWalletMainBundle:Providercoupon", options={"id" = "couponId"})
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', coupon)")
     * @JsonDecode
     */
    public function addToCouponAction(Request $request, Providercoupon $coupon)
    {
        $displayName = $coupon->getProgramname();
        $kind = $coupon->getKind();

        return $this->handleAddRequest(
            $request,
            (new Location())->setContainer($coupon),
            $kind,
            $displayName
        );
    }

    /**
     * @Route("/edit/{locationId}",
     *     name="aw_mobile_location_edit",
     *     methods={"GET", "POST"},
     *     requirements={"locationId" = "\d+"}
     * )
     * @ParamConverter("location", class="AwardWalletMainBundle:Location", options={"id" = "locationId"})
     * @Security("is_granted('ROLE_USER') and is_granted('EDIT', location)")
     * @JsonDecode
     */
    public function editAction(Request $request, Location $location, ProviderTranslator $providerTranslator)
    {
        if ($location->getSubAccount() || $location->getAccount()) {
            $account = $location->getSubAccount() ? $location->getSubAccount()->getAccountid() : $location->getAccount();

            $displayName = $account->getProviderid()
                ? $providerTranslator->translateDisplayNameByEntity($account->getProviderid())
                : $account->getProgramname();
        } elseif ($location->getProvidercoupon()) {
            $displayName = $location->getProvidercoupon()->getProgramname();
        } else {
            $displayName = null;
        }

        $kind = $location->getProviderKind();

        if (empty($displayName) || empty($kind)) {
            throw $this->createNotFoundException();
        }

        return $this->handleAddRequest(
            $request,
            $location,
            $kind,
            $displayName,
            $location->getLogin(),
            true
        );
    }

    /**
     * @Route("/delete/{locationId}",
     *     name="aw_mobile_location_delete",
     *     methods={"DELETE"},
     *     requirements={"locationId"="\d+"}
     * )
     * @ParamConverter("location", class="AwardWalletMainBundle:Location", options={"id" = "locationId"})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF') and is_granted('DELETE', location)")
     * @JsonDecode
     */
    public function deleteAction(Request $request, Location $location, LoyaltyLocation $loyaltyLocation)
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($location);
        $em->flush();

        return $this->successJsonResponse([
            'account' => $this->getAccountData($location),
            'locations' => $this->getLocationData($this->awTokenStorage->getBusinessUser(), $loyaltyLocation),
        ]);
    }

    protected function loadMapper()
    {
        $listManager = $this->accountListManager;
        $options = $this->optionsFactory
            ->createMobileOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $this->awTokenStorage->getBusinessUser())
            );

        return new class($listManager, $options) {
            /**
             * @var AccountListManager
             */
            private $accountListManager;
            /**
             * @var Options
             */
            private $options;

            public function __construct(AccountListManager $accountListManager, Options $options)
            {
                $this->accountListManager = $accountListManager;
                $this->options = $options;
            }

            public function getAccount($id)
            {
                return $this->accountListManager->getAccount($this->options, $id);
            }

            public function getCoupon($id)
            {
                return $this->accountListManager->getCoupon($this->options, $id);
            }
        };
    }

    /**
     * @param int $kind
     * @param string $displayName
     * @param bool $edit
     * @return JsonResponse
     */
    private function handleAddRequest(
        Request $request,
        Location $location,
        $kind,
        $displayName,
        $login = null,
        $edit = false
    ) {
        $user = $this->awTokenStorage->getBusinessUser();
        $locationsTotal = $this->loyaltyLocation->getCountTotal($user);

        if ($request->isMethod("PUT") && $locationsTotal >= ($max = $this->getMaxLocations())) {
            // TODO: translate
            return $this->errorJsonResponse(sprintf("You can't add more than %d locations.", $max));
        }
        $form = $this->createForm(LoyaltyLocationType::class, $location, [
            'method' => $edit ? 'POST' : 'PUT',
            'user' => $user,
            'edit' => $edit,
        ]);

        if ($this->awFormLoyaltyLocationHandlerMobile->handleRequest($form, $request)) {
            /** @var Location $newLocation */
            $newLocation = $form->getData()->getEntity();
            $response = [];

            if (!$edit) {
                $locationsTotal = $this->loyaltyLocation->getCountTotal($user);
                $locationsTracked = $this->loyaltyLocation->getCountTracked($user);
                $settings = $newLocation->getLocationSettings()->filter(function ($settings) use ($user) {
                    /** @var LocationSetting $settings */
                    return $settings->getUser()->getUserid() == $user->getUserid();
                });

                if ($locationsTracked >= $this->getMaxTrackedLocations()
                    && ($settings->isEmpty() || !$settings->first()->isTracked())
                    && ($locationsTotal - $locationsTracked) == 1) {
                    $response = ["warning" => true];
                }
            }

            return $this->successJsonResponse(array_merge([
                "locationId" => $newLocation->getId(),
                'account' => $this->getAccountData($newLocation),
                'locations' => $this->getLocationData($this->awTokenStorage->getBusinessUser(), $this->loyaltyLocation),
            ], $response));
        }

        return new JsonResponse(
            array_merge(
                [
                    'Kind' => $kind,
                    'DisplayName' => $this->desanitizer->tryDesanitizeChars($displayName),
                ],
                ['formData' => $this->formDehydrator->dehydrateForm($form)],
                !empty($login) ? ["Login" => $login] : []
            )
        );
    }

    private function getAccountData(Location $location)
    {
        $container = $location->getContainer();

        if ($container instanceof Providercoupon) {
            return $this->loadMapper()->getCoupon($container->getId());
        } elseif ($container instanceof Subaccount) {
            return $this->loadMapper()->getAccount($container->getAccountid()->getId());
        } else {
            return $this->loadMapper()->getAccount($container->getId());
        }
    }

    private function getLocationData(Usr $user, LoyaltyLocation $loyaltyLocations)
    {
        return [
            'total' => $loyaltyLocations->getCountTotal($user),
            'tracked' => $loyaltyLocations->getCountTracked($user),
        ];
    }

    private function getMaxLocations()
    {
        return $this->loyaltyLocationMax;
    }

    private function getMaxTrackedLocations()
    {
        return $this->trackedLocationsLimiter->getMaxTrackedLocations();
    }
}
