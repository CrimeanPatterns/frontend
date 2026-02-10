<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Contactus;
use AwardWallet\MainBundle\Entity\Repositories\AbRequestRepository;
use AwardWallet\MainBundle\Entity\Repositories\FaqRepository;
use AwardWallet\MainBundle\Entity\Repositories\UsrRepository;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\ContactUsAuthType;
use AwardWallet\MainBundle\Form\Type\ContactUsUnauthType;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Mailer;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\Template\User\NewYearHolidays;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\Headers\MobileHeaders;
use AwardWallet\MainBundle\Parameter\DefaultBookerParameter;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\ProgramStatus\DesktopAddAccountDescriptor;
use AwardWallet\MainBundle\Service\ProgramStatus\DesktopContactUsDescriptor;
use AwardWallet\MainBundle\Service\ProgramStatus\Finder;
use AwardWallet\MainBundle\Service\ProviderStatusHandler;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class ContactUsController extends AbstractController
{
    public const ACCOUNT_REMOVE_PHRASES = [
        "(cancel|delete|close|deactivate|terminate|disable)\\s+(my|the|this)\\s+((aw(ard\\s*wallet(\\.com)?)?|(whole|entire(?=\s+(account|acct|accnt|profile))))\\s+)?(account|acct|accnt|profile|membership|user)",
        "my\\s+(aw\s+)?(account|acct|accnt|profile|membership|user)\\s+(deleted|closed)",
    ];

    private TokenStorageInterface $tokenStorage;

    private UsrRepository $usrRepository;

    private LoggerInterface $logger;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        UsrRepository $usrRepository,
        LoggerInterface $logger
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->usrRepository = $usrRepository;
        $this->logger = $logger;
    }

    /**
     * @Route(
     *     "/contact",
     *     name="aw_contactus_index",
     *     defaults={"_canonical" = "aw_contactus_index_locale", "_alternate" = "aw_contactus_index_locale"},
     *     options={"expose"=true}
     * )
     * @Route(
     *     "/{_locale}/contact",
     *     name="aw_contactus_index_locale",
     *     defaults={"_locale"="en", "_canonical" = "aw_contactus_index_locale", "_alternate" = "aw_contactus_index_locale"},
     *     requirements={"_locale" = "%route_locales%"}
     * )
     */
    public function indexAction(
        FaqRepository $faqRepository,
        AbRequestRepository $abRequestRepository,
        GlobalVariables $globalVariables,
        Request $request,
        DefaultBookerParameter $defaultBookerParameter,
        ProviderStatusHandler $providerStatusHandler,
        Mailer $mailer,
        PageVisitLogger $pageVisitLogger,
        Environment $twigEnv
    ) {
        $twigEnv->addGlobal('webpack', true);

        // Store mobile headers in session for POST requests
        if ($request->isMethod('GET') && $this->isGranted('SITE_MOBILE_APP')) {
            $this->storeMobileHeadersInSession($request);
        }

        $faqs = $faqRepository->findBy(
            [
                'faqid' => [9, 2, 72],
                'visible' => true,
            ],
            [
                'rank' => 'ASC',
            ]
        );
        $contactUs = new Contactus();

        foreach (ContactUsAuthType::getRequesttypes() as $type => $keys) {
            foreach ($keys as $key) {
                foreach ([$key, strtolower($key)] as $k) {
                    $message = $request->query->get($k);

                    if (isset($message) && is_string($message)) {
                        $contactUs->setRequesttype($type);
                        $contactUs->setMessage($message);

                        break 3;
                    }
                }
            }
        }

        $isBusinessArea = $this->isGranted('SITE_BUSINESS_AREA');
        $user = $this->getAuthUser();
        $form = $user ?
            $this->createForm(ContactUsAuthType::class, $contactUs, [
                'disable_booking' => $isBusinessArea,
            ]) :
            $this->createForm(ContactUsUnauthType::class, $contactUs, [
                'disable_booking' => $isBusinessArea,
                'with_captcha' => $this->showCaptcha($request),
            ]);

        // Is POST?
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Check
            if ($form->isValid() && $contactUs->getRequesttype() !== 'Award Ticket Booking Requests') {
                $now = new \DateTime();

                if ($user) {
                    $extensionVer = $form->get('extensionVersion')->getViewData();

                    if (!empty($extensionVer)) {
                        $providerStatusHandler
                            ->setUser($user)
                            ->setUserExtensionVersion($extensionVer);
                    }
                    $contactUs->setUserid($user->getId());
                    $contactUs->setEmail($user->getEmail());
                    $contactUs->setFullname($user->getFullName());
                    $contactUs->setPhone($user->getPhone1());
                    $paymentStats = $this->usrRepository->getPaymentStatsByUser($user->getId());
                    $lifetimeContribution = $paymentStats['LifetimeContribution'];
                    $contactUs->setLifetimecontribution($lifetimeContribution);
                }

                $contactUs->setDatesubmitted($now);
                $contactUs->setUserip($request->getClientIp());
                // Save
                $em = $this->getDoctrine()->getManager();
                $em->persist($contactUs);
                $em->flush();
                // Send message
                // TODO: fixText remove
                $body = html_entity_decode(fixText($contactUs->getMessage()));
                $body .= "\n\nName: " . $contactUs->getFullname() . "\n";
                $uid = $contactUs->getUserid();

                if (isset($uid, $lifetimeContribution)) {
                    $body .= "Lifetime contribution: \$" . $lifetimeContribution . "\n";
                }

                if ($user) {
                    $business = $this->usrRepository->getBusinessByUser($user);

                    if (!empty($business)) {
                        $body .= 'Business Name: ' . $business->getFullName() . "\n";
                        $body .= 'Business UserID: ' . $business->getId() . "\n";
                    }
                }
                $body .= 'Email: ' . $contactUs->getEmail() . "\n";
                $body .= 'Phone: ' . $contactUs->getPhone() . "\n";
                $body .= 'Request Type: ' . $contactUs->getRequesttype() . "\n";

                if ($brandName = $globalVariables->getBrandFullName()) {
                    $body .= 'From: ' . $brandName . "\n";
                }

                $browser = self::getUserBrowser($request->headers->get('user_agent', 'unknown'));

                if (isset($uid)) {
                    $body .= 'UserID: ' . $uid . "\n";
                }

                $body .= 'Browser: ' . $browser . "\n";
                $body .= 'User-Agent: ' . $request->headers->get('user_agent', 'unknown') . "\n";

                // Get mobile extension version from current request or session
                $mobileInfo = $this->getMobileDataFromRequestOrSession($request);

                $v3 = $form->has('v3') ? $form->get('v3')->getViewData() : null;
                $mobileExtensionVersion = $mobileInfo['extensionVersion'] ?? null;

                if (is_string($mobileExtensionVersion) && !empty($mobileExtensionVersion)) {
                    $v3 = $mobileExtensionVersion;
                }

                $v3Text = !empty($v3) ? 'Enabled, v' . $v3 : 'Disabled';
                $isIOS = $this->isGranted('SITE_MOBILE_APP_IOS') || $mobileInfo['platform'] === 'ios';
                $isAndroid = $this->isGranted('SITE_MOBILE_APP_ANDROID') || $mobileInfo['platform'] === 'android';

                if ($isIOS || $isAndroid) {
                    $body .= sprintf("Mobile Platform: %s\n", $isIOS ? 'iOS' : 'Android');

                    if (empty($v3)) {
                        $v3Text = $isAndroid ? 'Enabled' : 'Unknown';
                    }
                }

                $body .= sprintf("Browser Extension: %s\n", $v3Text);
                $body .= 'IP: ' . $request->getClientIp() . "\n";
                $body .= 'Session: ' . ($request->getSession() ? substr($request->getSession()->getId(), -4) : 'unknown') . "\n";
                $shownData = $contactUs->getShownData();

                if (!empty($shownData)) {
                    $dataRows = explode("|", $shownData);

                    if (sizeof($dataRows)) {
                        foreach ($dataRows as $k => $v) {
                            $dataRows[$k] = $this->strip($v);

                            if (empty($dataRows[$k])) {
                                unset($dataRows[$k]);

                                continue;
                            }
                            $dataRows[$k] = '- ' . $dataRows[$k];
                        }
                        $count = sizeof($dataRows);

                        if ($count > 0 && $count <= 100) {
                            $body .= "\nThe user was shown the following messages before submitting this request:\n";
                            $body .= implode("\n", $dataRows) . "\n";
                        }
                    }
                }

                $message = $mailer->getMessage('contact_us');
                $message->setTo($mailer->getEmail('support'));
                $message->setSubject(sprintf(
                    '%s  request type: "%s", #%d',
                    $globalVariables->getSiteName(),
                    $contactUs->getRequesttype(),
                    $contactUs->getContactusid()
                ))
                    ->setBody($body, 'text/plain')
                    ->setFrom(key($mailer->getEmail('from')), $contactUs->getFullname())
                    ->setReplyTo($contactUs->getEmail(), $contactUs->getFullname());
                $mailer->send($message, [
                    Mailer::OPTION_FIX_BODY => false,
                ]);

                $startNYHolidays = new \DateTime('2020-12-31');
                $endNYHolidays = new \DateTime('2021-01-11');

                if ($now > $startNYHolidays && $now < $endNYHolidays) {
                    if ($user) {
                        $template = new NewYearHolidays($user, $isBusinessArea);
                    } else {
                        $template = new NewYearHolidays($contactUs->getEmail(), $isBusinessArea);
                    }
                    $template->name = $contactUs->getFullname();
                    $template->endDate = $endNYHolidays;
                    $nyMessage = $mailer->getMessageByTemplate($template);
                    $nyMessage->setSubject($message->getSubject());
                    $mailer->send($nyMessage, [
                        Mailer::OPTION_SKIP_DONOTSEND => true,
                    ]);
                }

                $form = $user ?
                    $this->createForm(ContactUsAuthType::class, new Contactus(), [
                        'disable_booking' => $isBusinessArea,
                    ]) :
                    $this->createForm(ContactUsUnauthType::class, new Contactus(), [
                        'disable_booking' => $isBusinessArea,
                        'with_captcha' => $this->showCaptcha($request),
                    ]);
                $send = true;
            }
        }

        // Booking
        $ref = 0;
        $isTicketBookingRequest = 0;
        $booker = $this->getBookerByRef($request, $defaultBookerParameter->get(), $ref);

        if ($user && !empty($ref)) {
            $countRequests = $abRequestRepository->getActiveRequestsCountByUser($user);
            $lastActiveRequestId = ($countRequests > 0) ? $abRequestRepository->getLastActiveRequestByUser($user)->getAbRequestID() : null;
            $isTicketBookingRequest = 1;

            if ($countRequests == 1) {
                $bookingRequestLink = $this->generateUrl('aw_booking_view_index', ['id' => $lastActiveRequestId]) . '?post={text}#respond-block';
            } elseif ($countRequests > 1) {
                $bookingRequestLink = $this->generateUrl('aw_booking_list_requests');
            } elseif ($countRequests == 0) {
                $bookingRequestLink = $this->generateUrl('aw_booking_add_index');
            }
        }

        if (!$request->headers->has(MobileHeaders::MOBILE_NATIVE)) {
            $pageVisitLogger->log(PageVisitLogger::PAGE_CONTACT_US);
        }

        return $this->render('@AwardWalletMain/ContactUs/index.html.twig', [
            'faqs' => $faqs,
            'form' => $form->createView(),
            'send' => isset($send),
            'booker' => $booker,
            'isTicketBookingRequest' => $isTicketBookingRequest,
            'bookingRequestCount' => (isset($countRequests)) ? $countRequests : 0,
            'bookingRequestLink' => (isset($bookingRequestLink)) ? $bookingRequestLink : '',
        ]);
    }

    /**
     * @Route(
     *     "/contact/search.{_format}",
     *     name="aw_contactus_programsearch",
     *     requirements={"_format" = "json"},
     *     methods={"POST"},
     *     options={"expose"=true}
     * )
     * @Security("is_granted('CSRF')")
     * @JsonDecode
     */
    public function programSearchAction(Request $request, DesktopContactUsDescriptor $contactUsDescriptor)
    {
        $msg = $request->get('msg');

        if (!is_string($msg) || empty($msg)) {
            return $this->json(null);
        }

        $user = $this->getAuthUser();
        $resultResponse = [
            'error' => '',
            'content' => '',
        ];

        if (
            preg_match("/(" . implode(")|(", self::ACCOUNT_REMOVE_PHRASES) . ")/ims", $msg)
        ) {
            $resultResponse['content'] = $this->renderView(
                '@AwardWalletMain/ContactUs/otherSearchResult.html.twig',
                [
                    'kind' => 'delete_profile',
                ]
            );

            return $this->json($resultResponse);
        }

        $result = $contactUsDescriptor->searchProviders($msg, $user);

        if (count($result) === 0) {
            $resultResponse['content'] = '';
        } else {
            $resultResponse['content'] = $this->renderView('@AwardWalletMain/ContactUs/programSearchResult.html.twig', ['programs' => $result]);
        }

        return $this->json($resultResponse);
    }

    /**
     * @Route("/check-status-program", name="aw_contactus_checkstatus", options={"expose"=true})
     * @Security("is_granted('CSRF') and is_granted('ROLE_USER')")
     * @JsonDecode
     */
    public function checkStatusProgramAction(
        Request $request,
        Finder $finder,
        DesktopAddAccountDescriptor $addAccountDescriptor
    ) {
        $msg = $request->get('msg');

        if (!is_string($msg) || empty($msg)) {
            throw $this->createNotFoundException();
        }

        $result = $finder->findProviders($msg, $addAccountDescriptor, $this->getAuthUser());

        if (count($result) === 0) {
            return $this->json(null);
        }

        return $this->json($this->renderView('@AwardWalletMain/ContactUs/programSearchResult.html.twig', [
            'programs' => $result,
            'withoutContainer' => true,
        ]));
    }

    public static function getUserBrowser($agent)
    {
        $pattern = "/(MSIE|Opera|Firefox|Chrome|Version|Opera Mini|Netscape|Konqueror|SeaMonkey|Camino|Minefield|Iceweasel|K-Meleon|Maxthon)(?:\/| )([0-9.]+)/";
        preg_match($pattern, $agent, $browser_info);

        if (!isset($browser_info[1]) || !isset($browser_info[2])) {
            return $agent;
        }
        [$_, $browser, $version] = $browser_info;

        if (preg_match("/(Opera|OPR)[\/ ]([0-9.]+)/i", $agent, $opera)) {
            return 'Opera ' . $opera[2];
        }

        if ($browser == 'MSIE') {
            preg_match("/(Maxthon|Avant Browser|MyIE2)/i", $agent, $ie);

            if ($ie) {
                return $ie[1] . ' based on IE ' . $version;
            }

            return 'IE ' . $version;
        }

        if ($browser == 'Firefox') {
            preg_match("/(Flock|Navigator|Epiphany)\/([0-9.]+)/", $agent, $ff);

            if ($ff) {
                return $ff[1] . ' ' . $ff[2];
            }
        }

        if (strpos('Edge', $agent) !== -1) {
            preg_match("/(Edge)(?:\/| )([0-9.]+)/", $agent, $result);

            if (sizeof($result) == 3) {
                return "Microsoft Edge $result[2]";
            }
        }

        if ($browser == 'Opera' && $version == '9.80') {
            return 'Opera ' . substr($agent, -5);
        }

        if ($browser == 'Version') {
            return 'Safari ' . $version;
        }

        if (!$browser && strpos($agent, 'Gecko')) {
            return 'Browser based on Gecko';
        }

        return $browser . ' ' . $version;
    }

    private function showCaptcha(Request $request)
    {
        if (!$this->isGranted('SITE_DEV_MODE')) {
            return true;
        }

        $captcha = $request->query->get('captcha');
        $wcaptcha = $request->query->get('wcaptcha');
        $session = $request->getSession();

        if ($captcha) {
            $session->set('contactus.captcha', true);

            return true;
        } elseif ($wcaptcha) {
            $session->set('contactus.captcha', false);

            return false;
        }

        return $session->get('contactus.captcha', true);
    }

    private function strip($str)
    {
        return preg_replace("/\s+/", " ", strip_tags(trim((string) $str)));
    }

    private function getBookerByRef(Request $request, int $defaultBooker, &$ref)
    {
        $ref = 0;
        $bookerRep = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbBookerInfo::class);
        $defBooker = $bookerRep->findOneBy(['UserID' => $defaultBooker]);
        $user = $this->getAuthUser();

        if ($user) {
            $cameFrom = $user->getCamefrom();

            if (!isset($cameFrom)) {
                $booker = $defBooker;
                $ref = 0;
            } else {
                $booker = $bookerRep->findOneBy(['SiteAdID' => $cameFrom]);

                if (!$booker) {
                    $booker = $defBooker;
                    $ref = 0;
                } else {
                    $ref = $booker->getSiteAdID();
                }
            }
        } else {
            $ref = $request->query->get('ref');

            if (isset($ref) || isset($_SESSION['ref'])) {
                $ref = isset($ref) ? intval($ref) : intval($_SESSION['ref']);
                $booker = $bookerRep->findOneBy(['SiteAdID' => $ref]);

                if (!$booker) {
                    $booker = $defBooker;
                    $ref = 0;
                } else {
                    $ref = $booker->getSiteAdID();
                }
            } else {
                $booker = $defBooker;
                $ref = 0;
            }
        }

        return $booker;
    }

    private function getBrowserExtension($user, $userAgent)
    {
        $result = "";

        if ($user->getExtensionversion() != '') {
            $result .= " ext v" . $user->getExtensionversion();

            if ($userAgent != $user->getExtensionbrowser()) {
                $result . " in " . self::getUserBrowser($user->getExtensionbrowser());
            }

            if ($user->getExtensionlastusedate() != '') {
                $result .= ", last used: " . $user->getExtensionlastusedate()->format('Y-m-d');
            }
        }

        return $result;
    }

    private function getAuthUser(): ?Usr
    {
        if (!$this->isGranted('ROLE_USER')) {
            return null;
        }

        if (
            !is_null($this->tokenStorage->getToken())
            && ($user = $this->tokenStorage->getToken()->getUser()) instanceof Usr
        ) {
            /** @var Usr $user */
            return $user;
        }

        return null;
    }

    private function storeMobileHeadersInSession(Request $request): void
    {
        $session = $request->getSession();

        if (!$session) {
            $this->logger->info('session not available, cannot store mobile headers');

            return;
        }

        $session->set('mobile_headers_data', $context = [
            'extensionVersion' => $request->headers->get(MobileHeaders::MOBILE_EXTENSION_VERSION, null),
            'platform' => $request->headers->get(MobileHeaders::MOBILE_PLATFORM, null),
            'version' => $request->headers->get(MobileHeaders::MOBILE_VERSION, null),
        ]);
        $this->logger->info('storing mobile headers in session', $context);
    }

    private function getMobileDataFromRequestOrSession(Request $request): array
    {
        $defaultData = [
            'extensionVersion' => null,
            'platform' => null,
            'version' => null,
        ];

        $session = $request->getSession();

        if (!$session) {
            $this->logger->info('session not available, cannot get mobile headers');

            return $defaultData;
        }

        $sessionData = $session->get('mobile_headers_data');

        if (!$sessionData || !is_array($sessionData)) {
            $this->logger->info('mobile headers not found in session, returning default data');

            return $defaultData;
        }

        $this->logger->info('retrieving mobile headers from session', $sessionData);

        return [
            'extensionVersion' => $sessionData['extensionVersion'] ?? null,
            'platform' => $sessionData['platform'] ?? null,
            'version' => $sessionData['version'] ?? null,
        ];
    }
}
