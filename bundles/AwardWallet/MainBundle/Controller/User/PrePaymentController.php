<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusPrepaid;
use AwardWallet\MainBundle\Entity\CartItem\AwPlusSubscription;
use AwardWallet\MainBundle\Entity\Repositories\CartRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Cart\CartUserInfo;
use AwardWallet\MainBundle\Globals\Cart\Manager as CartManager;
use AwardWallet\MainBundle\Globals\Cart\UpgradeCodeGenerator;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class PrePaymentController extends AbstractController
{
    public const NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD = 49.99;

    public const NEW_PRICE_201_ONE_YEAR_USD = 119.99;

    public const NEW_PRICE_201_HALF_YEAR_USD = 69.99;

    public const NEW_PRICE_201_ONE_MONTH_USD = 14.99;

    private AwTokenStorageInterface $tokenStorage;

    private RouterInterface $router;

    private CartManager $cartManager;

    private TranslatorInterface $translator;

    private LocalizeService $localizeService;

    private UsrRepository $userRepository;

    private CartRepository $cartRepository;

    private LoggerInterface $paymentLogger;

    private \Memcached $memcached;

    private UpgradeCodeGenerator $upgradeCodeGenerator;

    private bool $debug;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        RouterInterface $router,
        CartManager $cartManager,
        TranslatorInterface $translator,
        LocalizeService $localizeService,
        UsrRepository $userRepository,
        CartRepository $cartRepository,
        LoggerInterface $paymentLogger,
        \Memcached $memcached,
        UpgradeCodeGenerator $upgradeCodeGenerator,
        bool $debug
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->router = $router;
        $this->cartManager = $cartManager;
        $this->translator = $translator;
        $this->localizeService = $localizeService;
        $this->userRepository = $userRepository;
        $this->cartRepository = $cartRepository;
        $this->paymentLogger = $paymentLogger;
        $this->memcached = $memcached;
        $this->upgradeCodeGenerator = $upgradeCodeGenerator;
        $this->debug = $debug;
    }

    /**
     * @Route("/user/pre-payment", name="aw_pre_payment", defaults={"_canonical"="aw_pre_payment_locale", "_alternate"="aw_pre_payment_locale"}, options={"expose"=true})
     * @Route("/{_locale}/user/pre-payment", name="aw_pre_payment_locale", requirements={"_locale"="%route_locales%"}, defaults={"_locale"="en", "_canonical"="aw_pre_payment_locale", "_alternate"="aw_pre_payment_locale"}, options={"expose"=true})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function indexAction(Request $request, UserProfileWidget $userProfileWidget, Environment $twigEnv): Response
    {
        throw $this->createNotFoundException();
        $twigEnv->addGlobal('webpack', true);

        $userProfileWidget->setActiveItem('upgrade');
        [$user, $cartUserInfo] = $this->getUserAndCartUserInfo($request);

        if (is_null($user)) {
            return $this->redirectToRoute('aw_login', ['BackTo' => $this->router->generate('aw_pre_payment')]);
        }

        //        if (!$this->debug && !$user->hasRole('ROLE_STAFF')) {
        //            throw $this->createAccessDeniedException();
        //        }

        $title = $this->translator->trans('pre-payment.title', [
            '%email%' => $user->getEmail(),
            '%wrapperOn%' => '',
            '%wrapperOff%' => '',
            '%forWrapperOn%' => '',
            '%forWrapperOff%' => '',
        ], 'messages');

        if (empty($this->getAvailablePurchaseTypesByUser($user))) {
            return $this->render('@AwardWalletMain/User/prePayment.html.twig', [
                'data' => [
                    'title' => $title,
                    'error' => $this->getAwPlusPrepaidMaxError(),
                ],
            ]);
        }

        $activeAwSubscription = $this->cartRepository->getActiveAwSubscription($user);

        return $this->render('@AwardWalletMain/User/prePayment.html.twig', [
            'data' => [
                'title' => $title,
                'ref' => $user->getRefcode(),
                'hash' => $this->upgradeCodeGenerator->generateCode($user),
                'email' => $user->getEmail(),
                'price' => sprintf(
                    '%s / %s',
                    $this->localizeService->formatCurrency(AwPlusPrepaid::PRICE, 'USD', true, $request->getLocale()),
                    $this->translator->trans('years', ['%count%' => 1], 'messages')
                ),
                'dropdownOptions' => $this->getDropdownOptions($user, $request),
                'canBuyNewSubscription' => $this->canBuyNewSubscription($user, $activeAwSubscription),
                'membershipExpiration' => $user->getPlusExpirationDate() ? $user->getPlusExpirationDate()->format('c') : null,
                'appleSubscription' => $activeAwSubscription
                    && $user->getSubscription() == Usr::SUBSCRIPTION_MOBILE
                    && $activeAwSubscription->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE,
            ],
        ]);
    }

    /**
     * @Route("/user/pre-payment/pay", name="aw_pre_payment_pay", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @JsonDecode
     */
    public function payAction(Request $request, ExpirationCalculator $expirationCalculator): JsonResponse
    {
        throw $this->createNotFoundException();
        [$user, $cartUserInfo] = $this->getUserAndCartUserInfo($request);

        if (is_null($user)) {
            return JsonResponse::create([
                'error' => $this->translator->trans(
                    /** @Desc("User not found") */
                    'pre-payment.user_not_found', [], 'messages'
                ),
            ]);
        }

        //        if (!$this->debug && !$user->hasRole('ROLE_STAFF')) {
        //            return JsonResponse::create([
        //                'error' => 'Access denied',
        //            ]);
        //        }

        $availablePurchaseTypes = $this->getAvailablePurchaseTypesByUser($user);

        if (empty($availablePurchaseTypes)) {
            return JsonResponse::create([
                'error' => $this->getAwPlusPrepaidMaxError(),
            ]);
        }

        $purchaseType = $request->request->get('purchaseType');

        if (!is_numeric($purchaseType)) {
            return $this->getInvalidPurchaseTypeError();
        }

        $purchaseType = (int) $purchaseType;

        if (!in_array($purchaseType, $availablePurchaseTypes, true)) {
            return $this->getInvalidPurchaseTypeError();
        }

        $prepaidItem = $this->getPurchaseItemByType($purchaseType);
        $cart = $this->cartManager->createNewCart($cartUserInfo);
        $cart->setCalcDate(new \DateTime());
        $cart->addItem($prepaidItem);

        $activeAwSubscription = $this->cartRepository->getActiveAwSubscription($user);
        $canBuyNewSubscription = $this->canBuyNewSubscription($user, $activeAwSubscription);
        $addNewSubscription = $request->request->get('addSubscription');

        // if the user can buy a new subscription and the addSubscription flag is set to 1
        // then add a new subscription item to the cart
        if ($canBuyNewSubscription && is_numeric($addNewSubscription) && intval($addNewSubscription) === 1) {
            $subscriptionStartDate = $this->getNextExpirationDate($expirationCalculator, $user, $prepaidItem);
            $subscriptionItem = new AwPlusSubscription();
            $subscriptionItem->setStartDate(clone $subscriptionStartDate);
            $subscriptionItem->setScheduledDate(clone $subscriptionStartDate);
            $subscriptionItem->setDescription($this->translator->trans('cart.item.type.awplus-subscription.scheduled', [
                '%startDate%' => $this->localizeService->formatDateTime($subscriptionStartDate, 'short', null),
            ]));
            // set the new price for the subscription item
            $subscriptionItem->setPrice(self::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD);
            $cart->addItem($subscriptionItem);

            $prepaidItem->setDescription(
                $this->translator->trans(
                    'cart.item.type.awplus-multiple-years.renewal-notice',
                    [
                        '%date%' => $this->localizeService->formatDateTime($subscriptionStartDate, 'short', null),
                        '%price_per_period%' => $this->translator->trans(
                            'price_per_period',
                            [
                                '%price%' => $this->localizeService->formatCurrency(
                                    PrePaymentController::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD,
                                    'USD',
                                    true,
                                    $request->getLocale()
                                ),
                                '%period%' => $this->translator->trans(
                                    'years',
                                    ['%count%' => 1],
                                    'messages',
                                    $request->getLocale()
                                ),
                            ],
                            'messages',
                            $request->getLocale()
                        ),
                    ],
                    'messages',
                    $request->getLocale()
                )
            );
        } elseif (
            $activeAwSubscription
            && $user->getSubscription() == Usr::SUBSCRIPTION_MOBILE
            && $activeAwSubscription->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE
        ) {
            $prepaidItem->setDescription(
                $this->translator->trans(
                    'cart.item.type.awplus-multiple-years.apple-notice',
                    [
                        '%date%' => $this->localizeService->formatDateTime($this->getNextExpirationDate($expirationCalculator, $user, $prepaidItem), 'short', null),
                    ],
                    'messages',
                    $request->getLocale()
                )
            );
        } elseif ($canBuyNewSubscription) {
            $prepaidItem->setDescription(
                $this->translator->trans(
                    'cart.item.type.awplus-multiple-years.downgrade-notice',
                    [
                        '%date%' => $this->localizeService->formatDateTime($this->getNextExpirationDate($expirationCalculator, $user, $prepaidItem), 'short', null),
                    ],
                    'messages',
                    $request->getLocale()
                )
            );
        }

        $this->cartManager->save($cart);

        // TODO: cancel previous subscription
        $params = [
            'backTo' => $this->router->generate('aw_pre_payment'),
        ];

        if (is_null($cartUserInfo)) {
            $redirectUrl = $this->router->generate(
                'aw_cart_common_paymenttype',
                array_merge($params, [
                    'entry' => md5('aw_cart_common_paymenttype'),
                ]),
                RouterInterface::ABSOLUTE_URL
            );
        } else {
            $cart->setPaymenttype(Cart::PAYMENTTYPE_STRIPE_INTENT);
            $this->cartManager->save($cart);
            $redirectUrl = $this->router->generate(
                'aw_cart_stripe_orderdetails',
                array_merge($params, [
                    'entry' => md5('aw_cart_stripe_orderdetails'),
                ]),
                RouterInterface::ABSOLUTE_URL
            );
        }

        return JsonResponse::create([
            'success' => true,
            'redirect' => $redirectUrl,
        ]);
    }

    private function getNextExpirationDate(ExpirationCalculator $expirationCalculator, Usr $user, CartItem $prepaidItem): \DateTime
    {
        $expiration = $expirationCalculator->getAccountExpiration($user->getId());
        $expirationDateTs = \max($expiration['date'], time());
        $expirationNextDate = new \DateTime();
        $expirationNextDate->setTimestamp($expirationDateTs);
        $expirationNextDate->modify($prepaidItem->getDuration());

        return $expirationNextDate;
    }

    private function getDropdownOptions(Usr $user, Request $request): array
    {
        return it($this->getAvailablePurchaseTypesByUser($user))
            ->map(function (int $i) use ($request) {
                return [
                    'value' => $i,
                    'label' => $this->mbUcwords($this->translator->trans('interval_short.years', ['%count%' => $i], 'messages'), $request->getLocale()),
                    'priceRaw' => AwPlusPrepaid::PRICE * $i,
                    'priceFormatted' => $this->localizeService->formatCurrency(AwPlusPrepaid::PRICE * $i, 'USD', true, $request->getLocale()),
                    'newPriceRaw' => self::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD * $i,
                    'newPriceFormatted' => $this->localizeService->formatCurrency(self::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD * $i, 'USD', true, $request->getLocale()),
                ];
            })
            ->toArray();
    }

    private function canBuyNewSubscription(Usr $user, ?Cart $currentSubscriptionCart): bool
    {
        return
            (
                !$user->isAwPlus()
                || is_null($user->getSubscriptionType())
                || (
                    $user->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AWPLUS
                    && $user->getSubscription() != Usr::SUBSCRIPTION_STRIPE
                )
            )
            && !$this->userHasActiveAppleSubscription($user, $currentSubscriptionCart)
            && !$user->getCarts()->exists(function (int $key, Cart $cart) {
                /** @var CartItem|false $awNewSubscription */
                $awNewSubscription = $cart->getItemsByType([AwPlusSubscription::TYPE])->first();

                return $awNewSubscription !== false
                    && $cart->isPaid()
                    && $awNewSubscription->getPrice() == self::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD
                    && $awNewSubscription->isScheduled();
            });
    }

    private function userHasActiveAppleSubscription(Usr $user, ?Cart $currentSubscriptionCart): bool
    {
        return $currentSubscriptionCart
            && $user->isAwPlus()
            && $user->getSubscription() == Usr::SUBSCRIPTION_MOBILE
            && $user->getSubscriptionType() == Usr::SUBSCRIPTION_TYPE_AWPLUS
            && $currentSubscriptionCart->getPaymenttype() == Cart::PAYMENTTYPE_APPSTORE;
    }

    private function mbUcwords(string $string, string $lang): string
    {
        if ($lang === 'en') {
            return mb_convert_case($string, MB_CASE_TITLE, 'UTF-8');
        }

        return $string;
    }

    /**
     * @return array{Usr|null, CartUserInfo|null}
     */
    private function getUserAndCartUserInfo(Request $request): array
    {
        /** @var Usr|null $user */
        $user = $this->tokenStorage->getBusinessUser();

        if (is_null($user)) {
            $user = $this->getUserByRequest($request);

            if (is_null($user)) {
                return [null, null];
            }

            return [
                $user,
                new CartUserInfo($user->getId(), $user->getId(), true),
            ];
        }

        return [$user, null];
    }

    private function getUserByRequest(Request $request): ?Usr
    {
        $ref = $request->query->get('ref');
        $hash = $request->query->get('hash');

        if (empty($ref) || !is_string($ref) || empty($hash) || !is_string($hash)) {
            return null;
        }

        $user = $this->userRepository->findOneBy(['refcode' => $ref]);

        if (!$user) {
            $this->paymentLogger->warning(sprintf('user with refcode "%d" not found', $ref));

            return null;
        }

        $locker = new AntiBruteforceLockerService($this->memcached, 'pre_payment_hash_', 60, 5, 10, 'Invalid hash', $this->paymentLogger);

        if (!is_null($locker->checkForLockout($request->getClientIp())) || !is_null($locker->checkForLockout($ref))) {
            $this->paymentLogger->warning(sprintf('hash lockout for user "%s"', $ref));

            return null;
        }

        if ($hash !== $this->upgradeCodeGenerator->generateCode($user)) {
            $this->paymentLogger->warning(sprintf('hash mismatch for user "%s"', $ref));

            return null;
        }

        return $user;
    }

    private function getInvalidPurchaseTypeError(): JsonResponse
    {
        return JsonResponse::create([
            'error' => $this->translator->trans(
                /** @Desc("Invalid purchase type") */
                'pre-payment.invalid_purchase_type', [], 'messages'
            ),
        ], Response::HTTP_BAD_REQUEST);
    }

    private function getAwPlusPrepaidMaxError(): string
    {
        return $this->translator->trans(
            /** @Desc("You have already purchased the maximum prepaid period.") */
            'awplus-prepaid-max', [], 'messages'
        );
    }

    private function getPurchaseItemByType(int $type)
    {
        return (new AwPlusPrepaid())
            ->setCnt($type);
    }

    private function getAvailablePurchaseTypesByUser(Usr $user): array
    {
        $paidPrepaidYears = (int) it($user->getCarts()->toArray())
            ->filter(function (Cart $cart) {
                return $cart->isPaid() && $cart->hasItemsByType([AwPlusPrepaid::TYPE]);
            })
            ->map(fn (Cart $cart) => $cart->getItemsByType([AwPlusPrepaid::TYPE])[0]->getCnt())
            ->sum();

        if ($paidPrepaidYears >= 5) {
            return [];
        }

        return range(1, 5 - $paidPrepaidYears);
    }
}
