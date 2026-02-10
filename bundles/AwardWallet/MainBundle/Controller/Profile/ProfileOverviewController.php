<?php

namespace AwardWallet\MainBundle\Controller\Profile;

use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\CartItem;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\WebsiteSettingsType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Manager\MobileDeviceManager;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Security\RememberMe\RememberMeTokenProvider;
use AwardWallet\MainBundle\Security\SessionListener;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\DateTimeInterval\Formatter as DateTimeIntervalFormatter;
use AwardWallet\MainBundle\Service\NotificationSettings;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\ThemeResolver;
use AwardWallet\MainBundle\Service\Tripit\TripitUser;
use AwardWallet\MainBundle\Service\User\SubscriptionInfoHelper;
use AwardWallet\MainBundle\Service\UserAvatar;
use AwardWallet\WidgetBundle\Widget\UserProfileWidget;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileOverviewController extends AbstractController
{
    private AwTokenStorage $tokenStorage;
    private ?Usr $user;
    private EntityManagerInterface $em;
    private TranslatorInterface $translator;
    private LocalizeService $localizer;
    private NotificationSettings $notificationSettings;
    private RememberMeTokenProvider $tokenProvider;
    private SessionListener $sessionListener;
    private UserMailboxCounter $mailboxCounter;
    private ExpirationCalculator $expirationCalculator;
    private UserProfileWidget $profileWidget;
    private AuthorizationCheckerInterface $authorizationChecker;
    private DateTimeIntervalFormatter $intervalFormatter;
    private MobileDeviceManager $mobileDeviceManager;
    private UserAvatar $userAvatar;
    private SubscriptionInfoHelper $subscriptionInfo;

    public function __construct(
        AwTokenStorage $tokenStorage,
        EntityManagerInterface $em,
        TranslatorInterface $translator,
        LocalizeService $localizer,
        NotificationSettings $notificationSettings,
        RememberMeTokenProvider $tokenProvider,
        SessionListener $sessionListener,
        UserMailboxCounter $mailboxCounter,
        ExpirationCalculator $expirationCalculator,
        UserProfileWidget $profileWidget,
        AuthorizationCheckerInterface $checker,
        DateTimeIntervalFormatter $intervalFormatter,
        UserAvatar $userAvatar,
        MobileDeviceManager $mobileDeviceManager,
        SubscriptionInfoHelper $subscriptionInfo
    ) {
        $this->user = $tokenStorage->getUser();
        $this->tokenStorage = $tokenStorage;
        $this->em = $em;
        $this->translator = $translator;
        $this->localizer = $localizer;
        $this->notificationSettings = $notificationSettings;
        $this->tokenProvider = $tokenProvider;
        $this->sessionListener = $sessionListener;
        $this->mailboxCounter = $mailboxCounter;
        $this->expirationCalculator = $expirationCalculator;
        $this->profileWidget = $profileWidget;
        $this->authorizationChecker = $checker;
        $this->intervalFormatter = $intervalFormatter;
        $this->userAvatar = $userAvatar;
        $this->mobileDeviceManager = $mobileDeviceManager;
        $this->subscriptionInfo = $subscriptionInfo;
    }

    /**
     * @Route("/user/profile", host="%business_host%", name="aw_profile_overview_business")
     * @Security("is_granted('BUSINESS_ACCOUNTS')")
     * @Template("@AwardWalletMain/Profile/ProfileOverview/businessProfile.html.twig")
     * @return array
     */
    public function businessProfileAction(Request $request)
    {
        $this->profileWidget->setActiveItem('profile');

        return $this->getBusinessProfileOverviewData();
    }

    /**
     * @Route("/user/profile", name="aw_profile_overview", options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Template("@AwardWalletMain/Profile/ProfileOverview/userProfile.html.twig")
     * @return array
     */
    public function userProfileAction(Request $request, ThemeResolver $themeResolver, PageVisitLogger $pageVisitLogger)
    {
        $result = $this->getProfileOverviewData(['linkedBoxes']);
        $result['identifications'] = $this->sessionListener->groupSessions(
            $this->tokenProvider->fetchIdentificationByUserId($this->user->getUserid()),
            $request->getSession()->getId()
        );

        $appearanceItems = array_flip(WebsiteSettingsType::APPEARANCE_CHOICES);
        $appearanceCookie = $themeResolver->getCurrentTheme() ?? '';

        if (!isset($appearanceItems[$appearanceCookie])) {
            $appearanceCookie = '';
        }
        $result['appearance'] = $this->translator->trans(
            $appearanceItems[$appearanceCookie]
        );
        $tripitUser = new TripitUser($this->tokenStorage->getUser(), $this->em);
        $result['tripitHasAccessToken'] = $tripitUser->hasAccessTokens();
        $pageVisitLogger->log(PageVisitLogger::PAGE_MY_PROFILE);

        return $result;
    }

    public function getBusinessProfileOverviewData()
    {
        $business = $this->tokenStorage->getBusinessUser();

        return [
            'user' => $this->user,
            'business' => $business,
            'businessNotifications' => $this->authorizationChecker->isGranted('SITE_BOOKER_AREA') ?
                $this->notificationSettings->getBusinessSettingsView($this->user) :
                null,
        ];
    }

    public function getProfileOverviewData(array $features = [])
    {
        global $arPaymentTypeName;

        $features = array_flip($features);
        $userRep = $this->em->getRepository(Usr::class);
        $expirationDate = null;
        $getAwPlus = true;

        if ($this->user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
            $expiration = $this->expirationCalculator->getAccountExpiration($this->user->getId());

            if (!is_null($expiration['lastPrice'])) {
                $expirationDate = new \DateTime();
                $expirationDate->setTimestamp($expiration['date']);

                $diffExpiration = (new \DateTime())->diff($expirationDate);

                if (0 === $diffExpiration->days) {
                    $expiredAccount = 0;
                } elseif ($diffExpiration->invert) {
                    $expiredAccount = -$diffExpiration->days;
                }

                $getAwPlus = new \DateTime(MAX_AWPLUS_PERIOD) > $expirationDate;
            }
        }

        // detecting recurring
        // TODO: add bitcoin
        $currentSubscrCart = $this->user->getActiveSubscriptionCart();

        if (isset($currentSubscrCart)) {
            if ($currentSubscrCart->isCreditCardPaymentType() || $currentSubscrCart->isPayPalPaymentType()) {
                if (null === $this->user->getSubscription()) {
                    unset($currentSubscrCart);
                }
            }

            if (isset($currentSubscrCart) && isset($arPaymentTypeName[$currentSubscrCart->getPaymenttype()])) {
                $recurringKind = $currentSubscrCart->getPaymenttype();
                $recurringKindText = $this->translator->trans(/** @Ignore */ Cart::PAYMENT_TYPE_NAMES_PREFIX . $recurringKind);

                if (
                    $recurringKind === Cart::PAYMENTTYPE_CREDITCARD
                    && !empty($currentSubscrCart->getCreditcardtype())
                    && !empty($currentSubscrCart->getCreditcardnumber())
                    && preg_match('/\d{4}$/', $currentSubscrCart->getCreditcardnumber())
                ) {
                    $recurringCCType = $currentSubscrCart->getCreditcardtype();
                    $recurringCCLast4Digits = substr($currentSubscrCart->getCreditcardnumber(), -4);
                }
            }
        } else {
            $lastAwPlusCart = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Cart::class)
                ->getLastAwPlusCart($this->user);

            if ($lastAwPlusCart) {
                $isTrial = $lastAwPlusCart->hasItemsByType(CartItem::TRIAL_TYPES);
            }
        }

        return array_merge(
            [
                'user' => $this->user,
                'subscription' => [
                    'hasSubscription' => $this->user->hasAnyActiveSubscription(),
                    'expirationDate' => $this->subscriptionInfo->getExpirationDate(),
                    'expirationDateAt201' => $this->subscriptionInfo->getExpirationDateAt201(),
                    'paymentType' => isset($currentSubscrCart) ? $currentSubscrCart->getPaymenttype() : null,
                    'paymentTypeText' => $this->subscriptionInfo->getPaymentType(),
                ],
                'expirationDate' => $this->localizer->formatDate($expirationDate, 'short', $this->user->getLocale()),
                'expirationVerbal' => $expirationDate ? $this->intervalFormatter->formatDuration(
                    new \DateTime(),
                    $expirationDate,
                    true,
                    false,
                    true
                ) : 0,
                'expiredAccount' => $expiredAccount ?? null,
                'getAwPlus' => $getAwPlus,
                'recurringPayment' => (bool) $this->user->getPaypalrecurringprofileid(),
                'recurringKind' => $recurringKind ?? null,
                'recurringKindText' => $recurringKindText ?? null,
                'recurringCCType' => $recurringCCType ?? null,
                'recurringCCLast4Digits' => $recurringCCLast4Digits ?? null,
                'mobileSubscription' => isset($currentSubscrCart) && $currentSubscrCart->hasMobileAwPlusSubscription(),
                'isTrial' => $userRep->isTrialAccount($this->user),
                'avatarSrc' => $this->userAvatar->getUserUrl($this->user, false),
                'securityQuestionsCount' => $this->user->getSecurityQuestions()->count(),
                'userNotifications' => $this->notificationSettings->getSettingsView($this->user, [
                    NotificationSettings::KIND_MP,
                    NotificationSettings::KIND_WP,
                    NotificationSettings::KIND_EMAIL,
                ], NotificationSettings::OPTION_TABLE),
            ],
            isset($features['linkedBoxes']) ? ['linkedBoxes' => $this->mailboxCounter->total($this->user->getId())] : [],
            $this->getRegionalData(),
            ['onecard' => $this->em->getRepository(\AwardWallet\MainBundle\Entity\Onecard::class)->OneCardsCountByUser($this->user->getUserid())]
        );
    }

    public function getRegionalData()
    {
        $language = $this->translator->trans(/** @Ignore */ 'language.' . $this->user->getLanguage(), [], 'menu');
        $userRegion = $this->user->getRegion();
        $locale = $this->user->getLocale();

        $regionName = $userRegion ?
            \Locale::getDisplayRegion('-' . $userRegion)
            :
            $this->translator->trans(/** @Desc("Auto") */ 'auto');

        $date = new \DateTime('january 31 14:30');

        $regionHint = $this->localizer->formatNumberWithFraction(1000.00, 2, $locale);
        $regionHint .= ' | ';
        $regionHint .= $this->localizer->formatDate($date, 'short', $locale);
        $regionHint .= ' | ';
        $regionHint .= $this->localizer->formatTime($date, 'short', $locale);
        $isStaff = $this->authorizationChecker->isGranted('ROLE_STAFF');

        return [
            'language' => $language,
            'regionName' => $regionName,
            'regionHint' => $regionHint,
            'currency' => $isStaff && $this->user->getCurrency() ? $this->user->getCurrency() : null,
        ];
    }

    /**
     * @Route("/user/profile-idclear", name="aw_profile_idclear", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @return JsonResponse
     * @throws
     */
    public function clearAuth(Request $request)
    {
        $rememberMeTokenId = $request->request->get('rtid');
        $sessionId = $request->request->get('sid');

        $groupIdentify = $this->sessionListener->groupSessions(
            $this->tokenProvider->fetchIdentificationByUserId($this->user->getUserid()),
            $request->getSession()->getId()
        );

        $clearTokenId = $rememberMeTokenId ? [$rememberMeTokenId] : [];
        $clearSessId = $sessionId ? [$sessionId] : [];

        foreach ($groupIdentify as $item) {
            if (!empty($rememberMeTokenId) && !empty($item['RememberMeTokenID']) && $item['RememberMeTokenID'] == $rememberMeTokenId) {
                if (!empty($item['SessionID'])) {
                    $clearSessId[] = $item['SessionID'];
                }

                if (!empty($item['_group']['RememberMeTokenID'])) {
                    $clearTokenId = array_merge($clearTokenId, $item['_group']['RememberMeTokenID']);
                }

                if (!empty($item['_group']['SessionID'])) {
                    $clearSessId = array_merge($clearSessId, $item['_group']['SessionID']);
                }
            } elseif (!empty($sessionId) && isset($item['SessionID']) && $item['SessionID'] == $sessionId) {
                if (!empty($item['RememberMeTokenID'])) {
                    $clearTokenId[] = $item['RememberMeTokenID'];
                }

                if (!empty($item['_group']['SessionID'])) {
                    $clearSessId = array_merge($clearSessId, $item['_group']['SessionID']);
                }

                if (!empty($item['_group']['RememberMeTokenID'])) {
                    $clearTokenId = array_merge($clearTokenId, $item['_group']['RememberMeTokenID']);
                }
            }
        }

        $affected = 0;

        foreach ($clearTokenId as $tokenId) {
            $this->mobileDeviceManager->forgetUserByRememberMeTokenId($this->user, $tokenId);
            $affected += $this->sessionListener->invalidateUserSessionByRememberTokenId($this->user->getUserid(), $tokenId);
        }

        foreach ($clearSessId as $sessId) {
            $affected += $this->sessionListener->invalidateUserSession($this->user->getUserid(), $sessId);
        }

        return new JsonResponse(['success' => $affected > 0]);
    }
}
