<?php

declare(strict_types=1);

namespace AwardWallet\MainBundle\Controller\User;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Month;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription1Year;
use AwardWallet\MainBundle\Entity\CartItem\AT201Subscription6Months;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\Billing\RecurringManager;
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

class SubscriptionController extends AbstractController
{
    public const CHANGING_PRICE_DATE = 'December 18, 2024';

    private AwTokenStorageInterface $tokenStorage;

    private LocalizeService $localizeService;

    private TranslatorInterface $translator;

    private LoggerInterface $logger;

    private RecurringManager $recurringManager;

    private RouterInterface $router;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        LocalizeService $localizeService,
        TranslatorInterface $translator,
        LoggerInterface $paymentLogger,
        RecurringManager $recurringManager,
        RouterInterface $router
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->localizeService = $localizeService;
        $this->translator = $translator;
        $this->logger = $paymentLogger;
        $this->recurringManager = $recurringManager;
        $this->router = $router;
    }

    /**
     * @Route("/user/subscription/cancel", name="aw_user_subscription_get_cancel", methods={"GET"}, options={"expose"=true})
     * @Route("/user/subscription/lock-in-price", name="aw_user_subscription_lock_in_price", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function indexAction(Environment $twigEnv, Request $request)
    {
        if ('aw_user_subscription_lock_in_price' === $request->get('_route')) {
            return $this->redirect($this->router->generate('aw_user_subscription_get_cancel'));
        }

        $twigEnv->addGlobal('webpack', true);
        /** @var Usr $user */
        $user = $this->tokenStorage->getBusinessUser();

        return $this->render('@AwardWalletMain/User/subscriptionLockInPrice.html.twig', [
            'data' => $this->getUserOfferAndSubscriptionDetails($user, $request),
        ]);
    }

    /**
     * @Route("/user/subscription/cancel", name="aw_user_subscription_cancel", methods={"DELETE"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @JsonDecode
     */
    public function cancelSubscriptionAction(Request $request): JsonResponse
    {
        try {
            /** @var Usr $user */
            $user = $this->tokenStorage->getBusinessUser();

            if (!$user->hasAnyActiveSubscription()) {
                $this->logger->error(sprintf(
                    'error while canceling subscription for user %d: no active subscription',
                    $user->getId()
                ));

                return $this->jsonError('You do not have an active subscription');
            }

            if (!$user->canCancelActiveSubscription()) {
                $this->logger->error(sprintf(
                    'error while canceling subscription for user %d: not possible to cancel subscription type %s',
                    $user->getId(),
                    $user->getSubscriptionType()
                ));

                return $this->jsonError('Unable to cancel the subscription automatically');
            }

            switch ($user->getSubscription()) {
                case Usr::SUBSCRIPTION_PAYPAL:
                case Usr::SUBSCRIPTION_SAVED_CARD:
                case Usr::SUBSCRIPTION_STRIPE:
                case Usr::SUBSCRIPTION_MOBILE:
                    try {
                        $this->recurringManager->cancelRecurringPayment($user);
                    } catch (\Exception $e) {
                        return $this->jsonError('Failed to cancel recurring payment profile');
                    }

                    break;

                default:
                    $this->logger->error(sprintf(
                        'error while canceling subscription for user %d: unknown subscription type %d',
                        $user->getId(),
                        $user->getSubscription()
                    ));

                    return $this->jsonError('Unknown subscription type');
            }

            return new JsonResponse(['success' => true]);
        } catch (\Throwable $e) {
            $this->logger->error(sprintf(
                'error while canceling subscription for user %d: %s',
                $user->getId(),
                $e->getMessage()
            ));

            return $this->jsonError('An error occurred');
        }
    }

    /**
     * @Route("/user/subscription/cancel-apple", name="aw_user_applesubscription_cancel", options={"expose"=true})
     * @Security("is_granted('NOT_SITE_BUSINESS_AREA')")
     */
    public function cancelAppleSubscriptionAction(Environment $twigEnv, Request $request): Response
    {
        $twigEnv->addGlobal('webpack', true);

        $isFromApp = $request->query->get('fromapp') === '1';

        return $this->render('@AwardWalletMain/spa.html.twig', [
            'entrypoint' => 'user-settings',
            'data' => [],
            'renderSimpleHeader' => !$isFromApp,
            'renderLandingFooter' => !$isFromApp,
        ]);
    }

    private function jsonError(string $message): JsonResponse
    {
        return new JsonResponse(['error' => $message]);
    }

    /**
     * @throw AccessDeniedException
     */
    private function getUserOfferAndSubscriptionDetails(Usr $user, Request $request): array
    {
        global $arPaymentTypeName;

        $userHasActiveSubscription = $user->hasAnyActiveSubscription();
        $details = [
            'new-subscription-price' => $this->localizeService->formatCurrency(
                PrePaymentController::NEW_PRICE_AW_SUBSCRIPTION_ONE_YEAR_USD,
                'USD',
                true,
                $request->getLocale()
            ),
            'can-cancel' => $user->canCancelActiveSubscription(),
            'is-at201' => $user->hasActiveAt201Subscription(),
        ];

        if (!$userHasActiveSubscription) {
            $details['user-info'] = $this->translator->trans(
                /** @Desc("It looks like you don’t have an active AwardWallet Plus subscription at the moment. This means there’s no charge, and nothing to cancel. If you’re interested in the extra features AwardWallet Plus offers, feel free to %link_on%explore our subscription options%link_off%!") */
                'lock-in-price.no-active-aw-subscription',
                [
                    '%link_on%' => sprintf('<a href="%s" target="_blank">', $this->router->generate('aw_pricing', [], RouterInterface::ABSOLUTE_URL)),
                    '%link_off%' => '</a>',
                ],
            );
        } else {
            $currentSubscriptionCart = $user->getActiveSubscriptionCart();
            $lastFourCardDigits = null;

            if ($currentSubscriptionCart) {
                $paymentTypeName = $arPaymentTypeName[$currentSubscriptionCart->getPaymenttype()];

                if (
                    $currentSubscriptionCart->isCreditCardPaymentType()
                    && !empty($currentSubscriptionCart->getCreditcardtype())
                    && !empty($currentSubscriptionCart->getCreditcardnumber())
                    && preg_match('/\d{4}$/', $currentSubscriptionCart->getCreditcardnumber())
                ) {
                    $lastFourCardDigits = substr($currentSubscriptionCart->getCreditcardnumber(), -4);
                }
            } else {
                $paymentTypeName = $arPaymentTypeName[Cart::PAYMENTTYPE_CREDITCARD];
            }

            switch ($user->getSubscriptionType()) {
                case Usr::SUBSCRIPTION_TYPE_AWPLUS:
                    $pricePerPeriod = $this->translator->trans(
                        /** @Desc("%price% per %period%") */
                        'price_per_period',
                        [
                            '%price%' => $details['new-subscription-price'],
                            '%period%' => $this->translator->trans(
                                'years',
                                ['%count%' => 1],
                                'messages',
                                $request->getLocale()
                            ),
                        ],
                        'messages',
                        $request->getLocale()
                    );

                    if ($user->hasActiveDesktopSubscription()) {
                        if (isset($lastFourCardDigits)) {
                            $details['user-info'] = $this->translator->trans(
                                /** @Desc("Currently, your AwardWallet Plus subscription is set up via credit card ending in %card_last_digits%; if you do nothing, you will be charged a new price of %price_per_period% going forward. If you still wish to cancel your subscription press the button below:") */
                                'lock-in-price.active-aw-subscription-desktop-with-card',
                                [
                                    '%card_last_digits%' => '*' . $lastFourCardDigits,
                                    '%price_per_period%' => $pricePerPeriod,
                                ],
                                'messages',
                                $request->getLocale()
                            );
                        } else {
                            $details['user-info'] = $this->translator->trans(
                                /** @Desc("Currently, your AwardWallet Plus subscription is set up via %payment_type%; if you do nothing, you will be charged a new price of %price_per_period% going forward. If you still wish to cancel your subscription press the button below:") */
                                'lock-in-price.active-aw-subscription-desktop',
                                [
                                    '%payment_type%' => $paymentTypeName,
                                    '%price_per_period%' => $pricePerPeriod,
                                ],
                                'messages',
                                $request->getLocale()
                            );
                        }

                        $details['cancel-button-label'] = $this->translator->trans(
                            /** @Desc("Cancel AwardWallet Plus Subscription") */
                            'cancel-aw-subscription.button',
                            [],
                            'messages',
                            $request->getLocale()
                        );
                    } elseif ($user->hasActiveIosSubscription()) {
                        $details['user-info'] = $this->translator->trans(
                            /** @Desc("Currently, your AwardWallet Plus subscription is set up via Apple; if you do nothing, you will be charged %price_per_period% going forward. If you still wish to cancel your subscription, press the button below for instructions on how to cancel with Apple.") */
                            'lock-in-price.active-aw-subscription-ios',
                            [
                                '%price_per_period%' => $pricePerPeriod,
                            ],
                            'messages',
                            $request->getLocale()
                        );
                        $details['manual'] = true;
                        $details['cancel-button-label'] = $this->translator->trans(
                            /** @Desc("Cancel with Apple") */
                            'cancel-aw-subscription-with-apple.button',
                            [],
                            'messages',
                            $request->getLocale()
                        );
                    } elseif ($user->hasActiveAndroidSubscription()) {
                        $details['user-info'] = $this->translator->trans(
                            /** @Desc("Currently, your AwardWallet Plus subscription is set up via Google Play; if you do nothing, you will be charged a new price of %price_per_period% going forward. If you still wish to cancel your subscription press the button below:") */
                            'lock-in-price.active-aw-subscription-android',
                            [
                                '%price_per_period%' => $pricePerPeriod,
                            ],
                            'messages',
                            $request->getLocale()
                        );
                        $details['cancel-button-label'] = $this->translator->trans(
                            /** @Desc("Cancel Google Play Subscription") */
                            'cancel-aw-subscription-with-google.button',
                            [],
                            'messages',
                            $request->getLocale()
                        );
                    } else {
                        $this->logger->critical(sprintf(
                            'error while getting active subscription for user %d: unknown subscription type %d, cart id %d',
                            $user->getId(),
                            $user->getSubscription(),
                            $currentSubscriptionCart->getCartid()
                        ));

                        throw $this->createAccessDeniedException();
                    }

                    break;

                case Usr::SUBSCRIPTION_TYPE_AT201:
                    if ($currentSubscriptionCart->hasItemsByType([AT201Subscription1Year::TYPE])) {
                        $pricePerPeriod = $this->translator->trans(
                            /** @Desc("%price% per %period%") */
                            'price_per_period',
                            [
                                '%price%' => $this->localizeService->formatCurrency(
                                    PrePaymentController::NEW_PRICE_201_ONE_YEAR_USD,
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
                        );
                    } elseif ($currentSubscriptionCart->hasItemsByType([AT201Subscription6Months::TYPE])) {
                        $pricePerPeriod = $this->translator->trans(
                            /** @Desc("%price% per %period%") */
                            'price_per_period',
                            [
                                '%price%' => $this->localizeService->formatCurrency(
                                    PrePaymentController::NEW_PRICE_201_HALF_YEAR_USD,
                                    'USD',
                                    true,
                                    $request->getLocale()
                                ),
                                '%period%' => '6 ' . $this->translator->trans(
                                    'months',
                                    ['%count%' => 6],
                                    'messages',
                                    $request->getLocale()
                                ),
                            ],
                            'messages',
                            $request->getLocale()
                        );
                    } elseif ($currentSubscriptionCart->hasItemsByType([AT201Subscription1Month::TYPE])) {
                        $pricePerPeriod = $this->translator->trans(
                            /** @Desc("%price% per %period%") */
                            'price_per_period',
                            [
                                '%price%' => $this->localizeService->formatCurrency(
                                    PrePaymentController::NEW_PRICE_201_ONE_MONTH_USD,
                                    'USD',
                                    true,
                                    $request->getLocale()
                                ),
                                '%period%' => $this->translator->trans(
                                    'months',
                                    ['%count%' => 1],
                                    'messages',
                                    $request->getLocale()
                                ),
                            ],
                            'messages',
                            $request->getLocale()
                        );
                    } else {
                        $this->logger->error(sprintf(
                            'error while getting active subscription for user %d: unknown 201 subscription type %d, cart id %d',
                            $user->getId(),
                            $user->getSubscription(),
                            $currentSubscriptionCart->getCartid()
                        ));

                        throw $this->createAccessDeniedException();
                    }

                    if ($user->hasActiveDesktopSubscription()) {
                        if (isset($lastFourCardDigits)) {
                            $details['user-info'] = $this->translator->trans(
                                /** @Desc("Currently, your AwardTravel 201 subscription (which also includes an AwardWallet Plus subscription) is set up via credit card ending in %card_last_digits%; if you do nothing, you will be charged %price_per_period% going forward. If you still wish to cancel your subscription press the button below:") */
                                'lock-in-price.active-201-subscription-desktop-with-card',
                                [
                                    '%card_last_digits%' => '*' . $lastFourCardDigits,
                                    '%price_per_period%' => $pricePerPeriod,
                                ],
                                'messages',
                                $request->getLocale()
                            );
                        } else {
                            $details['user-info'] = $this->translator->trans(
                                /** @Desc("Currently, your AwardTravel 201 subscription (which also includes an AwardWallet Plus subscription) is set up via %payment_type%; if you do nothing, you will be charged %price_per_period% going forward. If you still wish to cancel your subscription press the button below:") */
                                'lock-in-price.active-201-subscription-desktop',
                                [
                                    '%payment_type%' => $paymentTypeName,
                                    '%price_per_period%' => $pricePerPeriod,
                                ],
                                'messages',
                                $request->getLocale()
                            );
                        }

                        $details['cancel-button-label'] = $this->translator->trans(
                            /** @Desc("Cancel AwardTravel 201 Subscription") */
                            'cancel-at201-subscription.button',
                            [],
                            'messages',
                            $request->getLocale()
                        );
                    } else {
                        $this->logger->error(sprintf(
                            'error while getting active subscription for user %d: unknown subscription type %d, cart id %d',
                            $user->getId(),
                            $user->getSubscription(),
                            $currentSubscriptionCart->getCartid()
                        ));

                        throw $this->createAccessDeniedException();
                    }

                    break;

                default:
                    $this->logger->error(sprintf(
                        'error while getting active subscription for user %d: unknown subscription type %d',
                        $user->getId(),
                        $user->getSubscriptionType() ?? 0
                    ));

                    throw $this->createAccessDeniedException();
            }

            if ($currentSubscriptionCart && $currentSubscriptionCart->isFirstTimeSubscriptionPending()) {
                $details['confirmation-title'] = $this->translator->trans(
                    /** @Desc("Cancel Subscription?") */
                    'first-time-subscription.cancel.confirmation.title',
                    [],
                    'messages',
                    $request->getLocale()
                );
                $details['confirmation-body'] = $this->translator->trans(
                    /** @Desc("If you cancel your subscription now, you’ll lose access to AwardWallet Plus features right away. Are you sure you want to continue?") */
                    'first-time-subscription.cancel.confirmation.body',
                    [],
                    'messages',
                    $request->getLocale()
                );
                $details['confirmation-button-no'] = $this->translator->trans(
                    /** @Desc("Keep My Subscription") */
                    'first-time-subscription.cancel.confirmation.button-no',
                    [],
                    'messages',
                    $request->getLocale()
                );
                $details['confirmation-button-yes'] = $this->translator->trans(
                    /** @Desc("Yes, Cancel") */
                    'first-time-subscription.cancel.confirmation.button-yes',
                    [],
                    'messages',
                    $request->getLocale()
                );
            }
        }

        return $details;
    }
}
