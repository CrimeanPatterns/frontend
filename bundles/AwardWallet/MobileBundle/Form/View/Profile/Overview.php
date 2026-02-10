<?php

namespace AwardWallet\MobileBundle\Form\View\Profile;

use AwardWallet\MainBundle\Controller\Profile\ProfileOverviewController;
use AwardWallet\MainBundle\Entity\Country;
use AwardWallet\MainBundle\Entity\Onecard;
use AwardWallet\MainBundle\Entity\Repositories\OnecardRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Scanner\UserMailboxCounter;
use AwardWallet\MainBundle\Service\GeoLocation\GeoLocation;
use AwardWallet\MainBundle\Service\LegacyUrlGenerator;
use AwardWallet\MainBundle\Service\NotificationSettings;
use AwardWallet\MainBundle\Service\User\SubscriptionInfoHelper;
use AwardWallet\MobileBundle\Form\View\Block\ApplicationIcon;
use AwardWallet\MobileBundle\Form\View\Block\CheckListItem;
use AwardWallet\MobileBundle\Form\View\Block\ColorTheme;
use AwardWallet\MobileBundle\Form\View\Block\DeviceLanguage;
use AwardWallet\MobileBundle\Form\View\Block\DisableAll;
use AwardWallet\MobileBundle\Form\View\Block\FlashMessage;
use AwardWallet\MobileBundle\Form\View\Block\FreeUserBanner;
use AwardWallet\MobileBundle\Form\View\Block\GroupTitle;
use AwardWallet\MobileBundle\Form\View\Block\LinkedAccount;
use AwardWallet\MobileBundle\Form\View\Block\Pincode;
use AwardWallet\MobileBundle\Form\View\Block\PushNotifications;
use AwardWallet\MobileBundle\Form\View\Block\SubTitle;
use AwardWallet\MobileBundle\Form\View\Block\TextProperty;
use AwardWallet\MobileBundle\Form\View\Block\TitledText;
use AwardWallet\MobileBundle\Form\View\Block\TwoFactorAuthentication;
use Doctrine\ORM\EntityManager;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;
use Symfony\Contracts\Translation\TranslatorInterface;

class Overview implements TranslationContainerInterface
{
    private TranslatorInterface $translator;

    private ApiVersioningService $apiVersioning;

    private ProfileOverviewController $userDataProvider;

    private UrlGeneratorInterface $router;

    private OnecardRepository $onecardRepository;

    private UsrRepository $userRepository;

    private AuthorizationChecker $authorizationChecker;

    private NotificationSettings $notificationSettings;

    private LocalizeService $localizer;

    private UserMailboxCounter $mailboxCounter;

    private LegacyUrlGenerator $legacyUrlGenerator;

    private SafeExecutorFactory $safeExecutorFactory;

    private GeoLocation $geoLocation;
    private SubscriptionInfoHelper $subscriptionInfo;

    public function __construct(
        TranslatorInterface $translator,
        ApiVersioningService $apiVersioning,
        ProfileOverviewController $userDataProvider,
        UrlGeneratorInterface $router,
        EntityManager $em,
        AuthorizationChecker $authorizationChecker,
        NotificationSettings $settings,
        LocalizeService $localizer,
        UserMailboxCounter $mailboxCounter,
        LegacyUrlGenerator $legacyUrlGenerator,
        SafeExecutorFactory $safeExecutorFactory,
        GeoLocation $geoLocation,
        SubscriptionInfoHelper $subscriptionInfo
    ) {
        $this->translator = $translator;
        $this->apiVersioning = $apiVersioning;
        $this->userDataProvider = $userDataProvider;
        $this->router = $router;
        $this->onecardRepository = $em->getRepository(Onecard::class);
        $this->userRepository = $em->getRepository(Usr::class);
        $this->authorizationChecker = $authorizationChecker;
        $this->notificationSettings = $settings;
        $this->localizer = $localizer;
        $this->mailboxCounter = $mailboxCounter;
        $this->legacyUrlGenerator = $legacyUrlGenerator;
        $this->safeExecutorFactory = $safeExecutorFactory;
        $this->geoLocation = $geoLocation;
        $this->subscriptionInfo = $subscriptionInfo;
    }

    public function createView(Usr $user, Request $request): array
    {
        $safeExec = $this->safeExecutorFactory;

        $tr = $this->translator;
        $isNative = $this->apiVersioning->supports(MobileVersions::NATIVE_APP);
        $r = $this->router;

        $result = [];

        $supportNewLinks = $this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS);
        $emailVerified = $user->getEmailverified();

        if (!$supportNewLinks || ($supportNewLinks && $emailVerified === EMAIL_UNVERIFIED)) {
            $flash = (
                $emailVerified == EMAIL_VERIFIED ?
                new FlashMessage($tr->trans('email.verified'), 'success') :
                new FlashMessage($tr->trans('email.not_verified', [
                    '%blockStart%' => '<span>',
                    '%blockEnd%' => '</span>',
                ], 'mobile'), 'fail')
            );

            $flash->setLink($r->generate('aw_mobile_send_verification_email'))
                ->setMethod('POST')
                ->setNotice($tr->trans('email.verify_popup.content'));

            $result[] = $flash;
        }

        // ###############################

        if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_INFO_LINKS)) {
            $result[] = (new TextProperty(
                $tr->trans('menu.blog', [], 'menu')
            ))->setFormLink($this->legacyUrlGenerator->generateAbsoluteUrl('/blog'));

            $result[] = (new TextProperty(
                $tr->trans('about.us.link', [], 'messages')
            ))->setFormLink($r->generate('aw_page_index', ['page' => 'about'], UrlGeneratorInterface::ABSOLUTE_URL));

            $result[] = (new TextProperty(
                $tr->trans('menu.privacy-notice', [], 'menu')
            ))->setFormLink($r->generate('aw_page_index', ['page' => 'privacy'], UrlGeneratorInterface::ABSOLUTE_URL));

            $result[] = (new TextProperty(
                $tr->trans('menu.terms-of-use', [], 'menu')
            ))->setFormLink($r->generate('aw_page_index', ['page' => 'terms'], UrlGeneratorInterface::ABSOLUTE_URL));

            $result[] = (new TextProperty(
                $tr->trans('menu.contact-us', [], 'menu')
            ))->setFormLink($r->generate('aw_contactus_index', [], UrlGeneratorInterface::ABSOLUTE_URL));

            //            if ($this->apiVersioning->supports(MobileVersions::FLIGHT_DEALS)) {
            //                $result[] = (new TextProperty(
            //                    $tr->trans('discounted-flight-deals', [], 'promotions')
            //                ))->setFormLink($r->generate('aw_promotions_index', [], UrlGeneratorInterface::ABSOLUTE_URL));
            //            }

            if ($this->apiVersioning->supports(MobileVersions::TRAVEL_SUMMARY_REPORT)) {
                $result[] =
                    (new TextProperty(
                        $tr->trans('trips.travel-summary-report', [], 'trips')
                    ))
                    ->setFormLink($r->generate('aw_travel_summary', [], UrlGeneratorInterface::ABSOLUTE_URL));
            }
        }

        // ###############################

        if (!($isRegionalSettingsSupported = $this->apiVersioning->supports(MobileVersions::REGIONAL_SETTINGS))) {
            $result[] = new DeviceLanguage($tr->trans('userinfo.language', [], 'mobile'));
        }

        // ###############################

        $groupTitleTrans = $tr->trans('personal_info.profile.title', [], 'mobile');

        if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
            $groupTitleTrans = $tr->trans('menu.userinfo.profile', [], 'menu');
        }

        if ($this->apiVersioning->supports(MobileVersions::MY_PROFILE_GROUP_TITLE)) {
            $result[] = new GroupTitle($groupTitleTrans);
        }

        $userPayLink = $r->generate('aw_users_pay', [], UrlGeneratorInterface::ABSOLUTE_URL);

        switch ($user->getAccountlevel()) {
            case ACCOUNT_LEVEL_FREE:
                if ($this->apiVersioning->supports(MobileVersions::UPGRADE_ACCOUNT_LEVEL_LINK)) {
                    $accountType = new TextProperty(
                        $tr->trans('account_type'),
                        $tr->trans('user.account_type.regular'),
                        null,
                        [
                            'url' => $r->generate('aw_users_pay'),
                        ]
                    );
                    $accountType->setFormLink('#upgradeAccount');
                } else {
                    $accountType = (new TextProperty(
                        $tr->trans('account_type'),
                        $tr->trans('user.account_type.regular')
                    ));
                }

                if ($isNative && empty($user->getPaypalrecurringprofileid())) {
                    $accountType->setFormLink('#upgrade');
                    $accountType->attrs = ['class' => 'silver'];

                    if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
                        $accountType->setFormLink($userPayLink);
                        $accountType->setType('upgrade');
                    }
                }

                $result[] = $accountType;

                break;

            case ACCOUNT_LEVEL_AWPLUS:
                $userInfo =
                    $safeExec(function () {
                        return $this->userDataProvider->getProfileOverviewData();
                    })
                    ->orValue([])();

                if (!$userInfo) {
                    break;
                }

                $supportsAwTrial = $this->apiVersioning->supports(MobileVersions::AW_PLUS_TRIAL);

                if ($this->apiVersioning->supports(MobileVersions::TEXT_PROPERTY_BLOCK_SUBHINT)) {
                    $result[] = $accountType = new TextProperty(
                        $tr->trans('account_type'),
                        ($userInfo['isTrial'] && $supportsAwTrial) ? $tr->trans('user.account_type.trial.text') : $tr->trans('account_type.awplus'),
                        $this->subscriptionInfo->getExpirationDate($user, true)
                    );
                    $accountType->subHint = mb_strtolower($tr->trans('on.date', ['%date%' => $userInfo['expirationDate']], 'mobile'));

                    if (
                        $isNative
                        && $userInfo['isTrial']
                        && $supportsAwTrial
                    ) {
                        $accountType->setFormLink($userPayLink);
                        $accountType->setType('upgrade');
                    }
                } else {
                    $result[] = $accountType = new TextProperty(
                        $tr->trans('account_type'),
                        ($userInfo['isTrial'] && $supportsAwTrial) ? $tr->trans('trial') : $tr->trans('account_type.awplus'),
                        $tr->trans('account_type.awplus.expires', [
                            '%expiration_date%' => $userInfo['expirationDate'],
                            '%expiration_verbal%' => $userInfo['expirationVerbal'],
                        ])
                    );
                }

                if ($this->apiVersioning->supports(MobileVersions::AWPLUS_SUBSCRIBE)) {
                    if (!empty($userInfo['recurringKind']) && !empty($userInfo['recurringKindText'])) {
                        $prop = new TextProperty(
                            $tr->trans('recurring'),
                            $userInfo['recurringKindText'],
                            null,
                            ['class' => 'silver', 'kind' => $userInfo['recurringKind']]
                        );
                        $prop->setFormLink('#cancelSubscription');

                        if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
                            $kind = 'desktop';

                            if ($userInfo['recurringKind'] === 8) {
                                $kind = 'ios';
                            }

                            if ($userInfo['recurringKind'] === 9) {
                                $kind = 'android';
                            }

                            $prop->setFormLink($this->legacyUrlGenerator->generateAbsoluteUrl("/m/subscription/cancel/$kind"));
                            $prop->setType('cancelSubscription');
                        }

                        $accountType->attrs = ['class' => 'silver'];
                        $result[] = $prop;
                    }
                }

                break;
        }

        if (isset($accountType)) {
            $accountType->setGroup($groupTitleTrans);
        }

        if ($this->apiVersioning->supports(MobileVersions::ACCOUNT_BALANCE_WATCH)) {
            $prop = new TextProperty(
                $tr->trans('cart.item.type.balancewatch-credit'),
                $user->getBalanceWatchCredits(),
                null,
                [
                    'url' => $r->generate('aw_users_pay_balancewatchcredit'),
                    'needUpgrade' => $user->getAccountlevel() === ACCOUNT_LEVEL_FREE,
                ]
            );
            $prop->setFormLink('#balanceCredits');

            if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
                $prop->setType('balanceCredits');
                $prop->setFormLink($r->generate('aw_users_pay_balancewatchcredit', [], UrlGeneratorInterface::ABSOLUTE_URL));
            }

            $prop->setGroup($groupTitleTrans);

            $result[] = $prop;
        }

        if ($this->apiVersioning->supports(MobileVersions::AWPLUS_VIA_COUPON_PROFILE_OVERVIEW)) {
            $result[] = (new TextProperty(
                $tr->trans('menu.upgrade-coupon', [], 'menu')
            ))->setGroup($groupTitleTrans)->setFormLink($r->generate('aw_mobile_usecoupon'))
                ->setFormTitle($this->apiVersioning->supports(MobileVersions::COUPON_ERROR_EXTENSION) ? null : $tr->trans('user.coupon.form.title'));
        }

        $onecards =
            $safeExec(function () use ($user) {
                return $this->onecardRepository->OneCardsCountByUser($user->getUserid())['Left'];
            })
            ->orValue(0)();

        if ($onecards > 0) {
            $result[] = new TextProperty(
                $tr->trans('userinfo.onecards', [], 'mobile'),
                $onecards
            );
        }

        if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
            $prop = new TextProperty(
                $tr->trans('connected.members', [], 'mobile')
            );
            $prop->setFormLink($r->generate('aw_user_connections', [], UrlGeneratorInterface::ABSOLUTE_URL));
            $result[] = $prop;
        }

        $deviceSupportTwoFactorSetup =
            $this->apiVersioning->supports(MobileVersions::TWO_FACTOR_AUTH_SETUP)
            && $user->twoFactorAllowed();
        $needSecurityGroup = $isNative || $deviceSupportTwoFactorSetup;

        if ($needSecurityGroup) {
            $securityGroupTitle = $tr->trans('userinfo.security', [], 'mobile');
            $result[] = new GroupTitle($securityGroupTitle);

            if ($isNative) {
                $result[] = (new Pincode())->setGroup($securityGroupTitle);
            }

            if ($deviceSupportTwoFactorSetup) {
                $result[] = (
                    $user->enabled2Factor() ?
                        (new TwoFactorAuthentication($tr->trans(/** @Desc("Turn off Two-factor Authentication") */ 'two-factor.disable', [], 'mobile'))) :
                        (new TextProperty($tr->trans(/** @Desc("Two-factor Authentication set up") */ 'two-factor.enable', [], 'mobile')))
                        ->setFormLink($r->generate('aw_profile_2factor', [], UrlGeneratorInterface::ABSOLUTE_URL))
                )
                    ->setGroup($securityGroupTitle);
            }
        }
        // ################################
        $personalInfoTitle = $tr->trans('personal_info');

        $makeLinkableToPersonal = function (/** @var TextProperty $linkable */ $linkable) use ($tr, $r, $personalInfoTitle) {
            return $linkable
                ->setGroup($personalInfoTitle)
                ->setFormLink($r->generate('aw_mobile_personal_info'))
                ->setFormTitle($tr->trans('user.personal.title'));
        };

        $result[] = (new GroupTitle($personalInfoTitle));

        $result[] = $makeLinkableToPersonal(new TextProperty(
            $tr->trans('login.login'),
            $user->getLogin()
        ));

        $questionsCount = $user->getSecurityQuestions()->count();
        $result[] = (new TextProperty(
            $tr->trans('login.security_question'),
            $questionsCount > 0 ?
                $tr->trans('login.security_question.configured', ['%count%' => $questionsCount, '%questions%' => $questionsCount]) :
                $tr->trans('login.security_question.not_configured')
        ))->setGroup($personalInfoTitle)
            ->setFormLink($r->generate('aw_mobile_profile_question'))
            ->setFormTitle($tr->trans('user.security_question.form.title'));

        $result[] = (new TextProperty(
            $tr->trans('login.pass'),
            str_repeat('•', 8)
        ))->setGroup($personalInfoTitle)->setFormLink($r->generate('aw_mobile_change_password'))
        ->setFormTitle($tr->trans('user.change-password.form.title'));

        $result[] = $makeLinkableToPersonal(new TextProperty(
            $tr->trans('login.first'),
            $user->getFirstname()
        ));

        if (StringUtils::isNotEmpty($midName = $user->getMidname())) {
            $result[] = $makeLinkableToPersonal(new TextProperty(
                $tr->trans('personal_info.middle_name'),
                $midName
            ));
        }

        $result[] = $makeLinkableToPersonal(new TextProperty(
            $tr->trans('login.name'),
            $user->getLastname()
        ));

        $result[] = (new TextProperty(
            $tr->trans('login.email'),
            $user->getEmail()
        ))->setGroup($personalInfoTitle)->setFormLink($r->generate('aw_mobile_change_email'))
        ->setFormTitle($tr->trans('user.change-email.form.title'));

        if ($this->apiVersioning->supports(MobileVersions::APPEARANCE_SETTINGS)) {
            $result[] = new GroupTitle($groupName = $tr->trans(/** @Desc("Appearance") */ 'personal_info.appearance'));
            $result[] = (new ApplicationIcon($tr->trans(/** @Desc("Application Icon") */ 'personal_info.application_icon')))
                ->setGroup($groupName);
            $result[] = (new ColorTheme($tr->trans(/** @Desc("Color Theme") */ 'personal_info.color_theme')))
                ->setGroup($groupName);
        }

        if (
            $this->apiVersioning->supports(MobileVersions::UNLINK_OAUTH)
            && $user->getOAuth()->count()
        ) {
            $result[] = (new SubTitle($userOauthTitle = $tr->trans('linked-oauth-accounts')));

            foreach ($user->getOAuth() as $userOAuth) {
                $result[] = (new LinkedAccount())
                    ->setProvider($userOAuth->getProvider())
                    ->setTitle(\ucfirst($userOAuth->getProvider()))
                    ->setEmail($userOAuth->getEmail())
                    ->setName($userOAuth->getFullName())
                    ->setId($userOAuth->getId())
                    ->setGroup($userOauthTitle);
            }
        }

        if ($isRegionalSettingsSupported) {
            $regionalFormLink = $r->generate('aw_mobile_regional_info');
            $regionalFormTitle = $tr->trans('personal_info.regional');
            $result[] = new GroupTitle($regionalFormTitle);

            $result[] = (new TextProperty(
                $tr->trans('personal_info.regional.language'),
                $tr->trans(/** @Ignore */ 'language.' . $user->getLanguage(), [], 'menu')
            ))
                ->setGroup($regionalFormTitle)
                ->setFormLink($regionalFormLink)
                ->setFormTitle($regionalFormTitle);

            $result[] = (new TextProperty(
                $tr->trans('label.country'),
                ($userRegion = $user->getRegion()) ?
                    \Locale::getDisplayRegion('-' . $userRegion) :
                    $tr->trans('auto'),
                implode(' | ', [
                    $this->localizer->formatNumberWithFraction(1000.00, 2, $locale = $user->getLocale()),
                    $this->localizer->formatDate($date = new \DateTime('january 31 14:30'), 'short', $locale),
                    $this->localizer->formatTime($date, 'short', $locale),
                ])
            ))
                ->setGroup($regionalFormTitle)
                ->setFormLink($regionalFormLink)
                ->setFormTitle($regionalFormTitle);

            if ($this->authorizationChecker->isGranted('ROLE_STAFF') && $user->getCurrency()) {
                $result[] = (new TextProperty(
                    $tr->trans('itineraries.currency', [], 'trips'),
                    $user->getCurrency()->getName()
                ))
                    ->setGroup($regionalFormTitle)
                    ->setFormLink($regionalFormLink)
                    ->setFormTitle($regionalFormTitle);
            }
        }

        if ($this->apiVersioning->supports(MobileVersions::ADVANCED_NOTIFICATIONS_SETTINGS)) {
            $groups =
                $safeExec(function () use ($user) {
                    return $this->notificationSettings->getSettingsView($user, [
                        NotificationSettings::KIND_MP,
                        NotificationSettings::KIND_EMAIL,
                    ]);
                })
                ->orValue([])();

            $isUsClientIp = $this->geoLocation->getCountryIdByIp($request->getClientIp()) === Country::UNITED_STATES;
            $notificationFreeVersion =
                $this->apiVersioning->supports(MobileVersions::NOTIFICATIONS_SETTINGS_FOR_FREE_USER)
                && (
                    $user->isFree()
                    || $this->userRepository->isTrialAccount($user)
                ) && $user->isUs() && $isUsClientIp;

            foreach ($groups as $notifications) {
                $result[] = new GroupTitle($notifications['group']);
                $fieldAttrs = [];

                if ($notifications['kind'] == NotificationSettings::KIND_MP) {
                    $group = 'push';
                } else {
                    $group = 'email';

                    if ($notificationFreeVersion) {
                        $result[] = new TitledText(
                            $tr->trans('email.notify.marketing.emails'),
                            $tr->trans('email.notify.unscribe.description')
                        );
                        $result[] = new DisableAll(
                            $tr->trans('notification.disable-all'),
                            $this->notificationSettings->getSettingsModel($user)->isEmailDisableAll(),
                            $notifications['group'],
                            $r->generate('aw_mobile_notifications', ['group' => $group]),
                            $notifications['group']
                        );
                        $result[] = new FreeUserBanner();
                        $fieldAttrs = [
                            'data-always-disabled' => true,
                        ];
                    }
                }

                foreach ($notifications['items'] as $title => $items) {
                    if ($title == NotificationSettings::APP_HEADER && !$isNative) {
                        continue;
                    }

                    if ($title != NotificationSettings::APP_HEADER) {
                        $result[] = new SubTitle($title);
                    }

                    foreach ($items as $notification) {
                        if (is_bool($notification['status'])) {
                            $block = new CheckListItem(
                                $notification['title'],
                                $notification['status'],
                                empty($fieldAttrs) || in_array($notification['attr']['formFieldName'] ?? null, ['emailOffers']) ? array_merge($notification['attr'], $fieldAttrs) : $notification['attr']
                            );
                        } else {
                            $block = new TextProperty(
                                $notification['title'],
                                $notification['status'],
                                null,
                                null
                            );
                        }

                        $block->setGroup($notifications['group']);

                        $result[] = $block->setFormLink($r->generate('aw_mobile_notifications', ['group' => $group]))
                            ->setFormTitle(
                                $this->apiVersioning->supports(MobileVersions::NOTIFICATIONS_SETTINGS_REMOVE_GROUP_TITLE)
                                ? $notifications['group']
                                : $tr->trans('personal_info.notifications')
                            );
                    }
                }
            }
        } else {
            $result[] = new GroupTitle($tr->trans('personal_info.notifications'));

            if ($isNative) {
                $result[] = (new PushNotifications($tr->trans('userinfo.notifications.alert', [], 'mobile')))
                    ->setGroup($tr->trans('personal_info.notifications'))
                    ->setFormLink($r->generate('aw_mobile_notifications'))
                    ->setFormTitle($tr->trans('personal_info.notifications'));
            }
            $notifications =
                $safeExec(function () use ($user) {
                    return $this->notificationSettings->getSettingsView($user, [NotificationSettings::KIND_EMAIL])[0]['items'];
                })
                ->orValue([])();

            foreach ($notifications as $items) {
                foreach ($items as $notification) {
                    if (is_bool($notification['status'])) {
                        $block = new CheckListItem($notification['title'], $notification['status']);
                    } else {
                        $block = new TextProperty(
                            $notification['title'],
                            /** @Ignore */
                            $tr->trans($notification['status'])
                        );
                    }
                    $result[] = $block
                        ->setGroup($tr->trans('personal_info.notifications'))
                        ->setFormLink($r->generate('aw_mobile_notifications'))
                        ->setFormTitle($tr->trans('personal_info.notifications'));
                }
            }
        }

        if ($this->apiVersioning->supports(MobileVersions::MAILBOX_SCANNER)) {
            $scannerTitle = $tr->trans('personal_info.site_settings.email_scanner');
            $result[] = new GroupTitle($scannerTitle);
            $result[] = new TextProperty(
                $tr->trans('personal_info.site_settings.email_scanner.mailboxes'),
                $safeExec(function () use ($user) {
                    return $this->mailboxCounter->total($user->getId());
                })
                ->orValue('-')()
            );
            $mailboxes = (new TextProperty($tr->trans('personal_info.site_settings.add_mailboxes')))
                ->setFormLink('#mailboxes');

            if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
                $mailboxes->setFormLink($r->generate('aw_usermailbox_view', [], UrlGeneratorInterface::ABSOLUTE_URL));
            }
            $mailboxes->setGroup($scannerTitle);
            $result[] = $mailboxes;
        }

        if ($this->apiVersioning->supports(MobileVersions::ADS_SETTINGS)) {
            $result[] = new GroupTitle($tr->trans('userinfo.other', [], 'mobile'));
            $userInfoOtherBlocks = [];
            $userInfoOtherBlocks[] = new CheckListItem(
                $tr->trans('show-screen-ads-after-logging', ['%awplus_only%' => ''], 'messages'),
                !$user->isAwPlus() || !$user->isSplashAdsDisabled(),
                null,
                $tr->trans('awplus_only')
            );

            $userInfoOtherBlocks[] = new CheckListItem(
                $tr->trans('use-affiliate-links-autologin', ['%awplus_only%' => ''], 'messages'),
                !$user->isAwPlus() || !$user->isLinkAdsDisabled(),
                null,
                $tr->trans('awplus_only')
            );

            $userInfoOtherBlocks[] = new CheckListItem(
                $tr->trans('show-card-ads-accountlist', ['%awplus_only%' => ''], 'messages'),
                !$user->isAwPlus() || !$user->isListAdsDisabled(),
                null,
                $tr->trans('awplus_only')
            );

            $userInfoOtherBlocks[] = new CheckListItem(
                $tr->trans('show-ads-blog-post-awplus-only', ['%awplus_only%' => ''], 'messages'),
                !$user->isAwPlus() || $user->isBlogPostAds(),
                null,
                $tr->trans('awplus_only')
            );

            foreach ($userInfoOtherBlocks as $userInfoOtherBlock) {
                $result[] = $userInfoOtherBlock
                    ->setFormLink($r->generate('aw_mobile_other_settings'))
                    ->setFormTitle($tr->trans('settings'));
            }
        }

        if ($this->apiVersioning->supports(MobileVersions::LOCATION_STORAGE)) {
            $formLink = $r->generate('aw_mobile_location_list');

            if ($this->apiVersioning->supports(MobileVersions::PROFILE_OVERVIEW_NEW_LINKS)) {
                $formLink = $this->legacyUrlGenerator->generateAbsoluteUrl('/m/profile/location/list');
            }
            $result[] = (new TextProperty(
                $tr->trans('locations_list')
            ))->setFormLink($formLink);
        }

        return $result;
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('personal_info.notifications'))->setDesc('Edit Notifications'),
            (new Message('email.not_verified', 'mobile'))->setDesc(
                '%blockStart% Your email has not been verified. %blockEnd% %blockStart% Please verify your email now. %blockEnd%'
            ),
            (new Message('personal_info.profile.title', 'mobile'))->setDesc('Profile'),
            (new Message('hide_cc_ads_acclist'))->setDesc('Hide credit card ads in the list of accounts'),
            (new Message('awplus_only'))->setDesc('AwardWallet Plus Only'),
            (new Message('settings'))->setDesc('Settings'),
            (new Message('locations_list'))->setDesc('Locations List'),
            (new Message('favorite_locations'))->setDesc('Favorite Locations'),
            (new Message('user.account_type.trial.text'))->setDesc('AwardWallet Plus Trial'),
        ];
    }
}
