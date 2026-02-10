<?php

namespace AwardWallet\MobileBundle\Controller;

use AwardWallet\MainBundle\Configuration\AwCache;
use AwardWallet\MainBundle\Controller\Profile\ProfileOverviewController;
use AwardWallet\MainBundle\Entity;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\ControllerTrait;
use AwardWallet\MainBundle\FrameworkExtension\Error\SafeExecutorFactory;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\MobileMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Globals\ApiVersioning\ApiVersioningService;
use AwardWallet\MainBundle\Globals\ApiVersioning\MobileVersions;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use AwardWallet\MainBundle\Scanner\Mobile\UserMailboxOwnerListLoader;
use AwardWallet\MainBundle\Service\AccountHistory\BankTransactionsAnalyser;
use AwardWallet\MainBundle\Service\AvatarJpegHelper;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;
use AwardWallet\MainBundle\Service\InAppPurchase\ProviderRegistry;
use AwardWallet\MainBundle\Service\LoyaltyLocation;
use AwardWallet\MainBundle\Service\MobileData\DataFormatter;
use AwardWallet\MainBundle\Service\MobileData\DiscoveredAccounts;
use AwardWallet\MainBundle\Service\NotificationSettings;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface;
use AwardWallet\MainBundle\Service\TrackedLocationsLimiter;
use AwardWallet\MainBundle\Service\UserAvatar;
use AwardWallet\MainBundle\Timeline\Formatter\Mobile\Helper;
use AwardWallet\MobileBundle\Form\View\Profile\Overview;
use AwardWallet\MobileBundle\View\Booking\Details;
use Doctrine\ORM\EntityManagerInterface;
use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

/**
 * @Route("/data")
 */
class DataController extends AbstractController
{
    use ControllerTrait;
    use JsonTrait;

    private LocalizeService $localizeService;
    private ProvidercouponRepository $providercouponRep;
    private \Memcached $memcached;
    private LoggerInterface $logger;

    public function __construct(LocalizeService $localizeService, ProvidercouponRepository $providercouponRep, \Memcached $memcached, LoggerInterface $logger)
    {
        $this->localizeService = $localizeService;
        $this->providercouponRep = $providercouponRep;
        $this->memcached = $memcached;
        $this->logger = $logger;
    }

    /**
     * @Route("/", name="awm_data")
     * @AwCache(etagContentHash="sha256", noCache=true, noStore=true, maxage=0, public=false)
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    public function dataAction(
        Request $request,
        ProfileOverviewController $profileOverviewController,
        LoggerInterface $logger,
        GlobalVariables $globalVariables,
        SafeExecutorFactory $safeExec,
        $googleApiKeyMobileMaps,
        $googleClientId,
        $googleIosClientId,
        TrackedLocationsLimiter $trackedLocationsLimiter,
        Reader $geoIpCountry,
        AuthorizationCheckerInterface $authorizationChecker,
        TranslatorHijacker $translatorHijacker,
        ApiVersioningService $apiVersioning,
        Overview $awMobileProfileOverview,
        OptionsFactory $optionsFactory,
        MobileMapper $mobileMapper,
        TranslatorInterface $translator,
        Advertise $advertise,
        $projectDir,
        AvatarJpegHelper $avatarJpegHelper,
        UserAvatar $userAvatar,
        ExpirationCalculator $expirationCalculator,
        BankTransactionsAnalyser $bankTransactionsAnalyser,
        ClientInterface $client,
        UserMailboxOwnerListLoader $userMailboxOwnerListLoader,
        ProviderRegistry $providerRegistry,
        NotificationSettings $notificationSettings,
        LoyaltyLocation $loyaltyLocation,
        DataFormatter $dataFormatter,
        DiscoveredAccounts $discoveredAccounts,
        Helper $awTimelineHelperMobile,
        Details $awMobileViewBookingDetails,
        BlogPostInterface $blogPost,
        AccountListManager $accountListManager
    ) {
        $isoCountry =
            $safeExec(function () use ($request, $geoIpCountry) {
                try {
                    if (
                        ($record = $geoIpCountry->country($request->getClientIp()))
                        && ($country = $record->country)
                    ) {
                        return strtolower($country->isoCode);
                    }
                } catch (AddressNotFoundException $e) {
                    // localhost?
                }

                return null;
            })
            ->orValue(null)();

        /** @var Usr $user */
        $user = $this->getCurrentUser();
        $logger->info('Mobile data', array_merge([
            '_aw_userid' => $user->getId(),
            '_aw_mobile_api_module' => 'data',
            '_aw_mobile_version' => str_replace('+', '_', $request->headers->get(MobileHeaders::MOBILE_VERSION)),
            '_aw_mobile_platform' => $request->headers->get(MobileHeaders::MOBILE_PLATFORM),
            '_aw_mobile_api' => 1,
            '_aw_mobile_locale' => $request->getLocale(),
        ], StringHandler::isEmpty($isoCountry) ? [] : ['_aw_mobile_country' => $isoCountry]));

        $userLockKey = "m_api_data_lock_" . $user->getId();

        if (!$this->memcached->add($userLockKey, time(), 90)) {
            $this->logger->info("there is other /m/api/data/ request", ["UserID" => $user->getId()]);

            return new Response('Too many parallel requests for this user', Response::HTTP_TOO_MANY_REQUESTS);
        }

        try {
            $isStaff = $authorizationChecker->isGranted('ROLE_STAFF', $user);

            if ($this->throttle($user->getId(), $isStaff)) {
                return new Response('Too many requests for this user', Response::HTTP_TOO_MANY_REQUESTS);
            }

            $isImpersonated = $authorizationChecker->isGranted('ROLE_IMPERSONATED', $user);
            $translatorHijacker->setContext('mobile');

            if ($apiVersioning->supports(MobileVersions::PROFILE_FORMS)) {
                $profileOverview = $awMobileProfileOverview->createView($user, $request);
            }

            $accountListOptions = null;
            // Account List
            $accounts =
                $safeExec(function () use ($isStaff, $isImpersonated, $user, $safeExec, &$accountListOptions, $optionsFactory, $mobileMapper, $accountListManager) {
                    $accountListOptions =
                        $optionsFactory
                            ->createMobileOptions(
                                (new Options())
                                    ->set(Options::OPTION_USER, $user)
                                    ->set(Options::OPTION_FORMATTER, $mobileMapper)
                                    ->set(Options::OPTION_SAFE_EXECUTOR_FACTORY, $safeExec)
                                    ->set(Options::OPTION_SKIP_ON_FAILURE, true)
                                    ->set(Options::OPTION_LOAD_MERCHANT_RECOMMENDATIONS, $isStaff || $isImpersonated)
                            );

                    return $accountListManager
                        ->getAccountList($accountListOptions)
                        ->getAccounts();
                })
                    ->orValue([])();

            // Repositories

            // Get list accounts
            $doctrine = $this->getDoctrine();

            $providerKinds = array_filter([
                PROVIDER_KIND_CREDITCARD => ['Name' => $translator->trans("track.group.card"),
                    'KindID' => PROVIDER_KIND_CREDITCARD],
                PROVIDER_KIND_AIRLINE => ['Name' => $translator->trans("track.group.airline"),
                    'KindID' => PROVIDER_KIND_AIRLINE],
                PROVIDER_KIND_HOTEL => ['Name' => $translator->trans("track.group.hotel"),
                    'KindID' => PROVIDER_KIND_HOTEL],
                PROVIDER_KIND_CAR_RENTAL => ['Name' => $translator->trans("track.group.rent"),
                    'KindID' => PROVIDER_KIND_CAR_RENTAL],
                PROVIDER_KIND_TRAIN => ['Name' => $translator->trans("track.group.train"),
                    'KindID' => PROVIDER_KIND_TRAIN],
                PROVIDER_KIND_CRUISES => ['Name' => $translator->trans("track.group.cruise"),
                    'KindID' => PROVIDER_KIND_CRUISES],
                PROVIDER_KIND_SHOPPING => ['Name' => $translator->trans("track.group.shop"),
                    'KindID' => PROVIDER_KIND_SHOPPING],
                PROVIDER_KIND_DINING => ['Name' => $translator->trans("track.group.dining"),
                    'KindID' => PROVIDER_KIND_DINING],
                PROVIDER_KIND_SURVEY => ['Name' => $translator->trans("track.group.survey"),
                    'KindID' => PROVIDER_KIND_SURVEY],
                PROVIDER_KIND_PARKING => $apiVersioning->supports(MobileVersions::PARKING_KIND) ? ['Name' => $translator->trans("track.group.parking"),
                    'KindID' => PROVIDER_KIND_PARKING] : null,
                PROVIDER_KIND_OTHER => ['Name' => $translator->trans("track.group.other"),
                    'KindID' => PROVIDER_KIND_OTHER],
            ]);

            $cardOffersEligible = false;

            if ($apiVersioning->supports(MobileVersions::CARD_OFFERS)) {
                $cardOffersEligible =
                    $safeExec(function () use ($user, $request, $apiVersioning, &$providerKinds, $advertise, $projectDir) {
                        $categoryAds = $advertise->getListByUser($user);
                        $imageRoot = realpath($projectDir . '/web/');
                        $cardOffersEligible = false;

                        foreach ($categoryAds as $category => $categoryAd) {
                            if (isset($providerKinds[$category])) {
                                $cardOffersEligible = true;
                                $scheme = $request->getScheme();
                                $host = $request->getHost();

                                $adParameters = [
                                    'title' => $categoryAd->title,
                                    'image' => file_exists($imagePath = realpath("{$imageRoot}/{$categoryAd->image}")) ?
                                        "{$scheme}://{$host}{$categoryAd->image}" : '',
                                    'description' => $categoryAd->description,
                                    'link' => [
                                        'url' => $categoryAd->link,
                                        'title' => '[Apply Now]',
                                    ],
                                ];

                                if ($apiVersioning->supports(MobileVersions::AD_CATEGORY_STRUCTURE)) {
                                    $providerKinds[$category]['ad'] = $adParameters;
                                } elseif ($apiVersioning->notSupports(MobileVersions::AD_CATEGORY_STRUCTURE)) {
                                    $providerKinds[$category]['ad'] = $this->renderView('@AwardWalletMobile/Account/List/cardOffer.html.twig', $adParameters);
                                }
                            }
                        }

                        return $cardOffersEligible;
                    })
                        ->orValue(false)();
            }

            if ($apiVersioning->supports(MobileVersions::CUSTOM_ACCOUNTS)) {
                $providerKinds['customAccount'] = [
                    'KindID' => 'custom',
                    'Name' => $translator->trans(/** @Desc("Custom Account") */ 'custom.account.list.title', [], 'mobile'),
                    'Notice' => $translator->trans(/** @Desc("Tracked Manually") */ 'custom.account.list.notice', [], 'mobile'),
                ];

                $providerKinds['customCoupon'] = [
                    'KindID' => 'coupon',
                    'Name' => $translator->trans(/** @Desc("Vouchers / Gift Cards") */ 'vouchers.gift.card.list.title', [], 'mobile'),
                    'Notice' => $translator->trans('custom.account.list.notice', [], 'mobile'),
                ];

                if ($apiVersioning->supports(MobileVersions::DOCUMENT_KIND)) {
                    $providerKinds['passport'] = [
                        'KindID' => Entity\Providercoupon::KEY_TYPE_PASSPORT,
                        'Name' => $translator->trans('document.passport.list.title', [], 'mobile'),
                    ];

                    $providerKinds['travelerNumber'] = [
                        'KindID' => Entity\Providercoupon::KEY_TYPE_TRAVELER_NUMBER,
                        'Name' => $translator->trans('document.traveler.number.list.title', [], 'mobile'),
                    ];

                    if ($apiVersioning->supports(MobileVersions::DOCUMENT_VACCINE_VISA_INSURANCE_TYPES)) {
                        $providerKinds['vaccineCard'] = [
                            'KindID' => Entity\Providercoupon::KEY_TYPE_VACCINE_CARD,
                            'Name' => $translator->trans(/** @Desc("Vaccine Card") */ 'document.vaccine.card.list.title', [], 'mobile'),
                        ];

                        $providerKinds['insuranceCard'] = [
                            'KindID' => Entity\Providercoupon::KEY_TYPE_INSURANCE_CARD,
                            'Name' => $translator->trans(/** @Desc("Insurance Card") */ 'document.insurance.card.list.title', [], 'mobile'),
                        ];

                        $providerKinds['visa'] = [
                            'KindID' => Entity\Providercoupon::KEY_TYPE_VISA,
                            'Name' => $translator->trans(/** @Desc("Visa") */ 'document.visa.list.title', [], 'mobile'),
                        ];

                        $providerKinds['driversLicense'] = [
                            'KindID' => Entity\Providercoupon::KEY_TYPE_DRIVERS_LICENSE,
                            'Name' => $translator->trans(/** @Desc("Drivers License") */ 'document.drivers.license.list.title', [], 'mobile'),
                        ];
                    }

                    if ($apiVersioning->supports(MobileVersions::DOCUMENT_PRIORITY_PASS)) {
                        $providerKinds['priorityPass'] = [
                            'KindID' => Entity\Providercoupon::KEY_TYPE_PRIORITY_PASS,
                            'Name' => $translator->trans('priority-pass'),
                        ];
                    }

                    $providerKinds['document'] = [
                        'Name' => $translator->trans("track.group.document"),
                        'KindID' => PROVIDER_KIND_DOCUMENT,
                        'hidden' => true,
                    ];
                }
            }

            $avatarImage = $avatarSrc = $avatarJpegHelper->getUserAvatarUrl($user, UrlGeneratorInterface::ABSOLUTE_URL);

            if (!isset($avatarImage)) {
                $avatarImage = $avatarSrc = $userAvatar->getUserUrl($user);
            }

            if ($user->getAccountlevel() == ACCOUNT_LEVEL_AWPLUS) {
                $expiresOn =
                    $safeExec(function () use ($request, $user, $expirationCalculator) {
                        $data = $expirationCalculator->getAccountExpiration($user->getId());

                        return $this->localizeService->formatDateTime(
                            (new \DateTime())->setTimestamp($data['date']),
                            'long',
                            null,
                            $request->getLocale()
                        );
                    })
                        ->orValue(false)();
            } else {
                $expiresOn = false;
            }

            $feedbacks =
                $safeExec(function () use ($doctrine, $user) {
                    $feedbackRep = $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Mobilefeedback::class);
                    $feedbacks = [];

                    foreach ($feedbackRep->findBy(['userid' => $user->getUserid()], ['date' => 'desc'], 5) as $feedback) {
                        $feedbacks[] = [
                            'action' => $feedback->getAction(),
                            'appVersion' => $feedback->getAppversion(),
                            'date' => $feedback->getDate()->getTimestamp(),
                        ];
                    }

                    return $feedbacks;
                })
                    ->orValue([])();

            $accountLevel = $globalVariables->accountLevels[$user->getAccountlevel()];

            $userInfo = [
                'UserID' => $user->getUserid(),
                "Login" => $user->getLogin(),
                "FullName" => $user->getFullName(),
                "AvatarSrc" => $avatarSrc,
                "AvatarImage" => $avatarImage,
                "UserEmail" => $user->getEmail(),
                'Logons' => $user->getLogoncount(),
                "Free" => $user->getAccountlevel() == ACCOUNT_LEVEL_FREE,
                'IsTrial' => $safeExec(function () use ($profileOverviewController) {
                    return $profileOverviewController->getProfileOverviewData()['isTrial'];
                })
                    ->orValue(false)(),
                "AccountLevel" => $accountLevel,
                "ExpiresOn" => $expiresOn,
                "OneCards" => $safeExec(function () use ($user, $doctrine) {
                    return $doctrine->getRepository(\AwardWallet\MainBundle\Entity\Onecard::class)->OneCardsCountByUser($user->getUserid())["Left"];
                })
                    ->orValue(0)(),
                'feedbacks' => $feedbacks,
                //            "restore" => $isImpersonated ? 0 : ($user->isIosRestoredReceipt() ? 0 : 1),
                "restore" => $user->isIosRestoredReceipt() ? 0 : 1, // temporarily
                "spentAnalysis" => $safeExec(function () use ($bankTransactionsAnalyser) {
                    $spentAnalysis = $bankTransactionsAnalyser->getSpentAnalysisInitial();

                    return
                        it($spentAnalysis['ownersList'] ?? [])
                            ->filter(function ($owner) {
                                return \count($owner['availableCards'] ?? []) > 0;
                            })
                            ->isNotEmpty();
                })
                    ->orValue(false)(),
                'centrifugeConfig' => $safeExec(function () use ($client) {
                    return $client->getClientData();
                })
                    ->orValue(null)(),
                'emailVerified' => $user->getEmailverified() == EMAIL_VERIFIED,
                'mailboxOwners' => $safeExec(fn () => it($userMailboxOwnerListLoader->load($user))
                    ->map(fn (Entity\Owner $owner) => $owner->isFamilyMember() ?
                        (string) $owner->getFamilyMember()->getid() :
                        'my'
                    )
                    ->toArray()
                )
                    ->orValue([])(),
                'updater3k' => true,
                'vaccineCardAccount' => ($vaccineAcc = $this->providercouponRep->getVaccineDocument($user, 'covid')) ? 'c' . $vaccineAcc : null,
                'refCode' => $user->getRefcode(),
            ];

            if (isset($profileOverview)) {
                $userInfo['overview'] = $profileOverview;
            }

            if ($cardOffersEligible) {
                $userInfo['advertiserDisclosureLink'] = 'https://awardwallet.com/blog/advertiser-disclosure/';
            }

            if ($apiVersioning->supports(MobileVersions::AWPLUS_SUBSCRIBE)) {
                $userInfo['products'] =
                    $safeExec(function () use ($request, $providerRegistry) {
                        $billingProvider = $providerRegistry->detectProvider($request, false);

                        return isset($billingProvider) ? $billingProvider->getSubscriptionsForSale() : [];
                    })
                        ->orValue([])();
            }

            if ($apiVersioning->supports(MobileVersions::LOUNGES) && ($updateDate = $user->getAvailableCardsUpdateDate())) {
                $userInfo['AvailableCardsUpdateDate'] = $updateDate->getTimestamp();
            }

            if ($apiVersioning->supports(MobileVersions::ADVANCED_NOTIFICATIONS_SETTINGS)) {
                $userNotifSettings = $notificationSettings
                    ->getSettingsModel($user);
                $userInfo['settings'] = $userNotifSettings->getMobileDumpedSettings();
            }

            if ($apiVersioning->supports(MobileVersions::LOCATION_STORAGE)) {
                [
                    'total' => $userInfo['locations']['total'],
                    'tracked' => $userInfo['locations']['tracked']
                ] =
                    $safeExec(function () use ($user, $loyaltyLocation) {
                        return [
                            'total' => $loyaltyLocation->getCountTotal($user),
                            'tracked' => $loyaltyLocation->getCountTracked($user),
                        ];
                    })
                        ->orValue(['total' => 0, 'tracked' => 0])();
            }

            if ($apiVersioning->supports(MobileVersions::AT201_SUBSCRIPTION_INFO)) {
                $userInfo['AT201'] = $user->getSubscriptionType() === Usr::SUBSCRIPTION_TYPE_AT201;
            }

            $userInfo['locale'] = str_replace('_', '-', $this->localizeService->getLocale() ?: $user->getLocale());
            $userInfo['language'] = $user->getLanguage();

            $googleMapsApiKey = $googleApiKeyMobileMaps;
            $regionForGoogle = strtoupper($user->getRegion());
            $languageForGoogle = str_replace('_', '-', $user->getLanguage());
            $userInfo['googleMapsEndpoints'] = [
                "https://maps.googleapis.com/maps/api/js?key={$googleMapsApiKey}&libraries=places&language={$languageForGoogle}&region={$regionForGoogle}",
            ];
            $userInfo['googleMailboxConfig'] = [
                'scopes' => [\Google_Service_Gmail::GMAIL_READONLY],
                'webClientId' => $googleClientId,
                'iosClientId' => $googleIosClientId,
            ];

            if (!StringHandler::isEmpty($isoCountry)) {
                $userInfo['country'] = $isoCountry;

                if ('cn' === $isoCountry) {
                    $userInfo['googleMapsEndpoints'] = ["http://maps.google.cn/maps/api/js?libraries=places&region=cn&language={$languageForGoogle}&key={$googleMapsApiKey}"];
                }
            }

            if ($authorizationChecker->isGranted('ROLE_TRANSLATOR', $user)) {
                $userInfo['translationTester'] = true;
            }

            if ($isStaff) {
                $userInfo['impersonate'] = true;
            }

            $userInfo = \array_merge(
                $userInfo,
                $dataFormatter->getData($user)
            );

            $json = [
                'accounts' => $accounts,
                'accountsOptions' => [
                    'changedPeriodDesc' => isset($accountListOptions) ?
                        $accountListOptions->get(Options::OPTION_CHANGE_PERIOD_DESC) :
                        null,
                ],
                'profile' => $userInfo,
                'constants' => [
                    'maxTracking' => $trackedLocationsLimiter->getMaxTrackedLocations(),
                    'providerKinds' => array_values($providerKinds),
                    //                'providerKindsSuppl' => $providerKindsSuppl,
                ],
            ];

            if ($apiVersioning->supports(MobileVersions::DISCOVERED_ACCOUNTS)) {
                $json['discoveredAccounts'] =
                    $safeExec(function () use ($user, $discoveredAccounts) {
                        return $discoveredAccounts->getList($user);
                    })
                        ->orValue([])();
            }

            if (!$apiVersioning->supports(MobileVersions::DATA_TIMESTAMP_OFF)) {
                $json['timestamp'] = \time();
            }

            if ($apiVersioning->supports(MobileVersions::TIMELINE)) {
                $json['timeline'] =
                    $safeExec(fn () => $awTimelineHelperMobile->getUserTimelines($user))
                        ->orElse(fn () => $awTimelineHelperMobile->getTimelineStub($user))
                        ->run();
            }

            if ($apiVersioning->supports(MobileVersions::BOOKING_VIEW)) {
                $safeExec(function () use ($user, &$json, $awMobileViewBookingDetails) {
                    $json['booking'] = $awMobileViewBookingDetails
                        ->getView($user);
                })();
            }

            $safeExec(function () use (&$json, $blogPost) {
                $rssData = $blogPost->fetchLastPost(1);

                if ($rssData && isset($rssData->title)) {
                    $json['blog'] = [
                        'title' => $rssData->title,
                        'datetime' => $rssData->postDate->format('l, F j, Y'),
                        'link' => $rssData->postURL,
                    ];
                }
            })();

            return $this->jsonResponse($json);
        } finally {
            $this->memcached->delete($userLockKey);
        }
    }

    /**
     * @Route("/small-avatar/user/{userIdDivBy1000}/{userId}-{timestamp}.jpg",
     *      name="awm_avatar_user",
     *      methods={"GET"},
     *      requirements={
     *          "userIdDivBy1000" = "\d{6,10}",
     *          "timestamp" = "\d{1,20}",
     *          "userId" = "\d{1,20}"
     *      }
     * )
     */
    public function avatarUserAction(
        string $userId,
        string $userIdDivBy1000,
        string $timestamp,
        EntityManagerInterface $entityManager,
        AvatarJpegHelper $avatarJpegHelper
    ): Response {
        $this->logger->info("avatarUserAction started");
        $userId = (int) $userId;
        $userIdDivBy1000Int = (int) $userIdDivBy1000;

        if (((int) ($userId / 1000)) !== $userIdDivBy1000Int) {
            throw $this->createNotFoundException();
        }

        $userRep = $entityManager->getRepository(Usr::class);
        /** @var Usr $user */
        $user = $userRep->find($userId);

        if ($user->getPicturever() !== (int) $timestamp) {
            throw $this->createNotFoundException();
        }

        $avatarSrc = $user->getAvatarSrc();

        if (empty($avatarSrc)) {
            throw $this->createNotFoundException();
        }

        return $this->getImageResponse($avatarJpegHelper->getImageDataByUser($user));
    }

    /**
     * @Route("/small-avatar/userAgent/{userAgentIdDivBy1000}/{userAgentId}-{timestamp}.jpg",
     *      name="awm_avatar_useragent",
     *      methods={"GET"},
     *      requirements={
     *          "userAgentIdDivBy1000" = "\d{6,10}",
     *          "timestamp" = "\d{1,20}",
     *          "userAgentId" = "\d{1,20}"
     *      }
     * )
     */
    public function avatarUserAgentAction(
        string $userAgentId,
        string $userAgentIdDivBy1000,
        string $timestamp,
        EntityManagerInterface $entityManager,
        AvatarJpegHelper $avatarJpegHelper
    ): Response {
        $userAgentId = (int) $userAgentId;
        $userAgentIdDivBy1000Int = (int) $userAgentIdDivBy1000;

        if (((int) ($userAgentId / 1000)) !== $userAgentIdDivBy1000Int) {
            throw $this->createNotFoundException();
        }

        $userAgentRep = $entityManager->getRepository(Entity\Useragent::class);
        /** @var Entity\Useragent $userAgent */
        $userAgent = $userAgentRep->find($userAgentId);

        if ($userAgent->getPicturever() !== (int) $timestamp) {
            throw $this->createNotFoundException();
        }

        $avatarSrc = $userAgent->getAvatarSrc();

        if (empty($avatarSrc)) {
            throw $this->createNotFoundException();
        }

        return $this->getImageResponse($avatarJpegHelper->getImageDataByUserAgent($userAgent));
    }

    protected function getImageResponse(?string $imageData): Response
    {
        if (\is_null($imageData)) {
            throw $this->createNotFoundException();
        }

        $response = new Response($imageData, 200, [
            'Content-Length' => strlen($imageData),
            'Content-Type' => 'image/jpg',
            'X-Content-Type-Options' => 'nosniff',
        ]);

        $lastModified = new \DateTimeImmutable();
        $response
            ->setLastModified(new \DateTime('@' . $lastModified->getTimestamp()))
            ->setExpires($expires = new \DateTime('@' . $lastModified->modify('+7 days')->getTimestamp()))
            ->setCache(['max_age' => $expires->getTimestamp() - $lastModified->getTimestamp()]);

        return $response;
    }

    private function throttle(int $userId, bool $isStaff): bool
    {
        $minuteThrottler = new \Throttler($this->memcached, 20, 3, 10);
        $key = "m_api_data_minute_$userId";

        if ($minuteThrottler->getDelay($key, true) > 0) {
            $this->logger->info("throttled /m/api/data/ user request, per minute", ["UserID" => $userId]);

            return true;
        }

        // increment
        $minuteThrottler->getDelay($key);

        $hourThrottler = new \Throttler($this->memcached, 600, 6, $isStaff ? 600 : 60);
        $key = "m_api_data_hour_$userId";

        if ($hourThrottler->getDelay($key) > 0) {
            $this->logger->info("throttled /m/api/data/ user request, per hour", ["UserID" => $userId]);

            return true;
        }

        // increment
        $hourThrottler->getDelay($key);

        return false;
    }
}
