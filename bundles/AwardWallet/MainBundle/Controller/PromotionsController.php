<?php

namespace AwardWallet\MainBundle\Controller;

use AwardWallet\MainBundle\Entity\Deal;
use AwardWallet\MainBundle\Entity\Dealmark;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Type\PromotionsRegionFilter;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\JsonTrait;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use Doctrine\ORM\EntityManagerInterface;
use JMS\TranslationBundle\Model\Message;
use JMS\TranslationBundle\Translation\TranslationContainerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class PromotionsController extends AbstractController implements TranslationContainerInterface
{
    use JsonTrait;

    private const SKY_SCANNER_DEALS_PER_PAGE = 10;

    private AwTokenStorageInterface $tokenStorage;
    private AuthorizationCheckerInterface $authorizationChecker;
    private RouterInterface $router;
    private LoggerInterface $logger;
    private SessionInterface $session;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        RouterInterface $router,
        LoggerInterface $logger,
        SessionInterface $session,
        EntityManagerInterface $entityManager
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->authorizationChecker = $authorizationChecker;
        $this->router = $router;
        $this->logger = $logger;
        $this->session = $session;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/promos", name="aw_promotions_index")
     * @Route("/{_locale}/promos", name="aw_promotions_index_locale", defaults={"_locale"="en"}, requirements={"_locale" = "%route_locales%"}, options={"expose"=true})
     * @Route("/promos/region/{regionID}", name="aw_promotions_index_region", requirements={"regionID" = "\d+|all|mask|clear"})
     * @Route("/promos/{name}", name="aw_promotions_index_name", methods={"GET"})
     * @param string $regionID
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(
        Request $request,
        $regionID = 'all',
        LocalizeService $localizeService,
        PageVisitLogger $pageVisitLogger
    ) {
        // user
        $user = $this->tokenStorage->getBusinessUser();

        if ($user instanceof Usr && $this->authorizationChecker->isGranted('ROLE_USER')) {
            $userID = $user->getUserid();
        } else {
            $userID = null;
        }

        // repositories
        $repUa = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class);
        $repAcc = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $repRegion = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Region::class);

        if (!empty($userID)) {
            // connection
            $connection = $this->entityManager->getConnection();
            $repUa->setAgentFilters($userID, $repUa::ALL_USERAGENTS, false, true);
            // form filter, regions
            $form = $this->createForm(PromotionsRegionFilter::class, ['regionid' => $regionID]);

            $sql = "
                SELECT a.ProviderID,
                    IF(ap.Val IS NOT NULL AND pp.Kind = " . PROPERTY_KIND_NUMBER . ", ap.Val, a.Login) AccountNumber,
                    p.DeepLinking,
                    " . SQL_USER_NAME . " as UserName,
                    a.Balance,
                    p.Code ProviderCode,
                    p.BalanceFormat,
                    pp.Kind,
                    a.AccountID
                FROM Account a USE INDEX (idx_Account_UserID, idx_Account_UserAgentID)
                    LEFT JOIN Provider p ON a.ProviderID = p.ProviderID
                    LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
                    LEFT JOIN (
                        SELECT AccountShare.UserAgentID, 
                            AccountID, 
                            AgentID, 
                            AccessLevel
                        FROM UserAgent, 
                            AccountShare 
                        WHERE 
                            UserAgent.UserAgentID = AccountShare.UserAgentID
                            AND UserAgent.AgentID = ?
                ) ash ON a.AccountID = ash.AccountID        
                    LEFT JOIN AccountProperty ap ON ap.AccountID = a.AccountID
                    LEFT JOIN ProviderProperty pp ON ap.ProviderPropertyID = pp.ProviderPropertyID 
                        AND pp.Kind = " . PROPERTY_KIND_NUMBER . "  
                    LEFT JOIN Usr u ON a.UserID = u.UserID        
                WHERE a.UserID = u.UserID 
                    AND ( {$repUa->userAgentAccountFilter} )
            ";
            $stmt = $connection->executeQuery($sql,
                [$userID],
                [\PDO::PARAM_INT]
            );
            $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $isDeepLinkingProviders = $accountsData = [];

            foreach ($accounts as $account) {
                $isDeepLinkingProviders[$account['ProviderID']] = $account['DeepLinking'];

                if (!isset($accountsData[$account['ProviderID']])) {
                    $accountsData[$account['ProviderID']] = [];
                }

                if (!isset($accountsData[$account['ProviderID']][$account['AccountID']]) || $account['Kind'] == 1) {
                    $account['FormatedBalance'] = $repAcc->formatFullBalance($account['Balance'], $account['ProviderCode'], $account['BalanceFormat'], false, $localizeService->getThousandsSeparator(), $localizeService->getDecimalPoint());
                    $accountsData[$account['ProviderID']][$account['AccountID']] = $account;
                }
            }

            $sql = "
                SELECT p.DisplayName,
                    d.Title,
                    d.Description,
                    d.Link,
                    d.Link AS RegistrationLink,
                    d.DealsLink,
                    d.ButtonCaption,
                    d.AutologinProviderID,
                    d.BeginDate,
                    d.EndDate,
                    d.DealID,
                    IF(dm.Readed IS NULL,0,dm.Readed) MarkRead,
                    IF(dm.Applied IS NULL,0,dm.Applied) MarkApplied,
                    IF(dm.Follow IS NULL,0,dm.Follow) MarkFollow,
                    IF(dm.Manual IS NULL,0,dm.Manual) MarkManual,
                    IF(a.ProviderID IS NOT NULL,1,0) MyPromo,
                    a.TotalBalance,
                    p.DeepLinking,
                    a.Count,
                    a.AccountID,
                    a.ProviderID AS MyProviderID,
                    p.ProviderID,
                    IF(d.CreateDate > DATE_ADD(NOW(), INTERVAL -7 DAY), 1, 0) IsNew,
                    rp.RelatedProviders,
                    p.AutoLogin,
                    d.AffiliateLink,
                    dr.RegionNames,
                    dr.RegionIDs
                FROM Deal d 
                    LEFT JOIN Provider p ON p.ProviderID = d.ProviderID
                    LEFT JOIN DealMark dm ON dm.DealID = d.DealID
                        AND dm.UserID = ?
                    LEFT JOIN (
                        SELECT DealID,
                            (SUM(Follow)+SUM(Applied)) TotalFollowApplied,
                            (SUM(Follow)+SUM(Manual)) TotalFollowManual
                        FROM DealMark
                        GROUP BY DealID
                    ) dmTotals ON dmTotals.DealID = d.DealID
                    LEFT JOIN (
                        SELECT GROUP_CONCAT(DISTINCT Region.Name) AS RegionNames,
                            GROUP_CONCAT(DISTINCT Region.RegionID) AS RegionIDs,
                            DealID
                        FROM DealRegion
                        JOIN Region ON Region.RegionID = DealRegion.RegionID
                        GROUP BY DealID
                    ) dr ON dr.DealID = d.DealID
                    LEFT JOIN (
                        SELECT 
                            a.ProviderID,
                            SUM(a.TotalBalance) AS TotalBalance,
                            COUNT(a.AccountID) AS Count,
                            if(COUNT(a.AccountID)=1,max(a.AccountID),NULL) AS AccountID
                        FROM Account a USE INDEX (idx_Account_UserID, idx_Account_UserAgentID)
                            LEFT JOIN UserAgent ua ON a.UserAgentID = ua.UserAgentID
                            LEFT JOIN (
                                SELECT AccountShare.UserAgentID, 
                                    AccountID, 
                                    AgentID, 
                                    AccessLevel
                                FROM UserAgent, 
                                    AccountShare 
                                WHERE 
                                    UserAgent.UserAgentID = AccountShare.UserAgentID
                                    AND UserAgent.AgentID = ?
                            ) ash ON a.AccountID = ash.AccountID
                            , Usr u
                        WHERE a.UserID = u.UserID
                            AND ({$repUa->userAgentAccountFilter})
                        GROUP BY a.ProviderID
                    ) a ON a.ProviderID = IF(d.AutologinProviderID IS NULL,d.ProviderID,d.AutologinProviderID)
                    LEFT JOIN (
                        SELECT GROUP_CONCAT(p.DisplayName) AS RelatedProviders,
                            DealID 
                        FROM DealRelatedProvider d 
                        JOIN Provider p USING(ProviderID)
                        GROUP BY DealID
                    ) rp ON rp.DealID = d.DealID
                    WHERE (NOW() >= d.BeginDate AND NOW() <= d.EndDate)
                ORDER BY MyPromo DESC, 
                    a.TotalBalance DESC,
                    p.DisplayName,
                    dmTotals.TotalFollowManual DESC,
                    d.TimesClicked DESC,
                    d.CreateDate DESC
            ";
            $stmt = $connection->executeQuery($sql,
                [$userID, $userID],
                [\PDO::PARAM_INT, \PDO::PARAM_INT]
            );

            $deals = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            $relatedProviders = [];
            $schemaDeal = [
                'total' => 0,
                'unread' => 0,
                'deals' => [],
            ];
            $promotions = [
                'my' => $schemaDeal,
                'other' => $schemaDeal,
            ];
            $dealsStuct = [];
            $sideKey = [1 => 'my', 0 => 'other'];

            // region param
            if (!preg_match("/\d+|all/", $regionID)) {
                $regionID = 'all';
            }

            foreach ($deals as $deal) {
                // filter region
                $deal['DealsLink'] = 'Deals';
                $deal['AffiliateLink'] = 'Affiliate';
                $deal['Link'] = 'Link';
                $deal['Continents'] = "";

                // refs #9861
                if (strstr($deal['Description'], '<div class="cardsDiv1">')) {
                    $deal['Description'] = preg_replace('#<li>(.*?)\r#s', '', $deal['Description'], 1);
                }

                $promoRegions = [];
                $allRegionParents = [];
                $markedRegions = [];
                $excluded = [];

                if (isset($deal['RegionNames']) && isset($deal['RegionIDs'])) {
                    $markedRegions = array_combine(explode(',', $deal['RegionIDs']), explode(',', $deal['RegionNames']));

                    foreach ($markedRegions as $markedRegionID => $markedRegionName) {
                        $regionParents = [];
                        $repRegion->findParentRegions($markedRegionID, $regionParents);

                        if (is_array($regionParents)) {
                            $allRegionParents += $regionParents;

                            if (!((string) $regionID == 'all') && !isset($regionParents[$regionID])) {
                                $excluded[] = $markedRegionID;
                            }
                        }
                    }

                    foreach ($markedRegions as $markedRegionID => $markedRegionName) {
                        // show only leaf regions, do not show continent if it chosen in form dropdown list, do not show region if it has no form selected region in it's parents
                        if (!isset($allRegionParents[$markedRegionID]) && ($markedRegionID !== $regionID) && !in_array($markedRegionID, $excluded)) {
                            $promoRegions[$markedRegionID] = $markedRegionName;
                        }
                    }
                    $deal['Continents'] = implode(', ', $promoRegions);
                }

                if (((string) $regionID == 'all')
                        || (((int) $regionID > 0) && (isset($allRegionParents[(int) $regionID]) || isset($markedRegions[$regionID])))) {
                    // create provider array schema
                    if (!isset($promotions[$sideKey[$deal['MyPromo']]]['deals'][$deal['ProviderID']])) {
                        $promotions[$sideKey[$deal['MyPromo']]]['deals'][$deal['ProviderID']] = $schemaDeal;
                    }
                    // totals
                    $promotions[$sideKey[$deal['MyPromo']]]['total']++;
                    $promotions[$sideKey[$deal['MyPromo']]]['deals'][$deal['ProviderID']]['total']++;

                    // mark unreads
                    if (!$deal['MarkRead']) {
                        $promotions[$sideKey[$deal['MyPromo']]]['unread']++;
                        $promotions[$sideKey[$deal['MyPromo']]]['deals'][$deal['ProviderID']]['unread']++;
                    }
                    // deals data of provider
                    $promotions[$sideKey[$deal['MyPromo']]]['deals'][$deal['ProviderID']]['deals'][] = $deal;
                }
            }
        }

        if (empty($userID) /* && empty($flightDeals) */) {
            return $this->redirect($this->router->generate('aw_login', ['BackTo' => $this->router->generate('aw_promotions_index')]));
        }

        // render
        $response = $this->render('@AwardWalletMain/Promotions/newIndex.html.twig', [
            'isDeepLinkingProviders' => $isDeepLinkingProviders ?? false,
            'accountsData' => $accountsData ?? [],
            'promotions' => $promotions ?? [],
            'form' => (isset($form)) ? $form->createView() : null,
            /*
            'flightDeals' => [
                'list' => !empty($flightDeals) ? $formatter->format($flightDeals) : null,
                'currentPage' => 1,
            ],
            */
        ]);
        $pageVisitLogger->log(PageVisitLogger::PAGE_PROMOS);

        return $response;
    }

    /**
     * @Security("is_granted('CSRF')")
     * @Route("/promos/{action}.{_format}",
     *      name="aw_promotions_ajax",
     *      methods={"POST"},
     *      requirements={
     *          "_format" = "json",
     *          "action" = "click|mark|getPopupFrame|markProviderAll|markFollow|markApply|markManual"
     *      })
     * @param string $action
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function ajaxAction(Request $request, $action = 'mark')
    {
        switch ($action) {
            case 'mark': return $this->mark($request);

            case 'click': return $this->click($request);

            case 'getPopupFrame': return $this->getPopupFrame($request);

            case 'markProviderAll': return $this->mark($request, true);

            case 'markFollow': return $this->markDealPre($request, 'Follow');

            case 'markApply': return $this->markDealPre($request, 'Applied');

            case 'markManual': return $this->markDealPre($request, 'Manual');
        }
    }

    public function mark(Request $request, $isProvider = false)
    {
        $dealID = intval($request->get('dealID'));
        $dealIDs = $request->get('dealIDs');
        $status = intval($request->get('status'));
        $providerID = intval($request->get('providerID'));
        $repDeal = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Deal::class);

        $dealIDs = $dealIDs ? explode(',', $dealIDs) : [];

        $resultResponse = [
            'error' => '',
            'content' => '',
        ];

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            $userID = $this->tokenStorage->getBusinessUser()->getUserid();

            if ($isProvider) {
                if (!empty($providerID)) {
                    if (!count($dealIDs)) {
                        $deals = $repDeal->findBy(['providerid' => $providerID]);
                    } else {
                        $deals = $repDeal->findByDealid($dealIDs);
                    }

                    foreach ($deals as $deal) {
                        $this->markDeal($deal->getDealid(), $userID, $status, 'Readed');
                    }
                    $resultResponse['content'] = 'OK';
                } else {
                    $this->logger->critical('ProviderID : is empty');
                    $resultResponse['error'] = 'Invalid Request';
                }
            } else {
                if ($dealID > 0) {
                    $this->markDeal($dealID, $userID, $status, 'Readed');
                    $resultResponse['content'] = 'OK';
                } else {
                    $this->logger->error('DealID : is empty');
                    $resultResponse['error'] = 'Invalid Request';
                }
            }
        } else {
            $resultResponse['error'] = 'You are not logged in'; /* checked */
        }

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $resultResponse,
        ]);
    }

    public function click(Request $request)
    {
        $dealID = intval($request->request->get('dealID'));

        $resultResponse = [
            'error' => '',
            'content' => '',
        ];

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            if ($dealID > 0) {
                $this->updateTimesClicked($dealID);
                $resultResponse['content'] = 'OK';
            } else {
                $this->logger->critical('DealID : is empty');
                $resultResponse['error'] = 'Invalid Request';
            }
        } else {
            $resultResponse['error'] = 'You are not logged in'; /* checked */
        }

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $resultResponse,
        ]);
    }

    public function getPopupFrame(Request $request)
    {
        if (!$this->authorizationChecker->isGranted('ROLE_USER')) {
            return new JsonResponse("auth required", 400);
        }
        // get params
        $dealID = intval($request->request->get('dealID'));
        $providerID = intval($request->request->get('providerID'));
        $action = $request->request->get('action');
        $action = empty($action) ? 'reg' : $action;
        $userID = $this->tokenStorage->getBusinessUser()->getUserid();
        $repAccount = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        // deal not found
        if (empty($dealID)) {
            $this->logger->critical('Deal is empty: $dealID = ' . $dealID);
            $resultResponse['error'] = 'Invalid request';
        }
        // update clicked
        $this->updateTimesClicked($dealID);
        // is deep linking
        $provider = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->find($providerID);
        $deepLinking = false;

        if (!$provider) {
            $this->logger->warning('Provider not found: $providerID = ' . $providerID);
            $resultResponse['error'] = 'Invalid request';
        }

        if ($provider->getDeeplinking() == DEEP_LINKING_SUPPORTED) {
            $deepLinking = true;
        }
        // get deal
        $deal = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Deal::class)->find($dealID);

        if (!$deal) {
            $this->logger->warning('Deal not found: $dealID = ' . $dealID);
            $resultResponse['error'] = 'Invalid request';
        }

        // set link
        switch ($action) {
            case 'details':
            case 'autologin': $link = 'DeepDealsLink';

                break;

            default:
                $link = 'DeepLink';
        }

        $resultResponse = [
            'error' => '',
            'content' => '',
        ];

        $accountSql = $repAccount->getAccountsSQLByUser($userID, "AND p.ProviderID = " . $providerID, null, null, null, false, true);
        $connection = $this->entityManager->getConnection();
        $stmt = $connection->executeQuery($accountSql);
        $accounts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $accountsData = $row = [];

        foreach ($accounts as $account) {
            $row['Balance'] = $repAccount->formatFullBalance($account['Balance'], $account['ProviderCode'], $account['BalanceFormat'], false);
            $row['UserName'] = $account['UserName'];
            $row['AccountNumber'] = $repAccount->getAccountNumberByAccountID($account['ID']);
            $row['AccountNumber'] = !isset($row['AccountNumber']) ? $account['Login'] : $row['AccountNumber'];
            $row['AccountID'] = $account['ID'];
            $accountsData[] = $row;
            // TODO
            $this->session->set('RedirectTo' . $account['ID'], $link);
        }

        // render popup
        $resultResponse['content'] = $this->render('@AwardWalletMain/Promotions/newpopupData.html.twig', [
            'accountsData' => $accountsData,
            'link' => htmlspecialchars_decode($link),
            'dealID' => $dealID,
        ])->getContent();

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $resultResponse,
        ]);
    }

    /**
     * @Route("/promos/redirect/{dealID}", name="aw_promotions_redirect", requirements={"dealID" = "\d+"})
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function redirectAction($dealID, Request $request)
    {
        $targetUrl = $request->query->get('Goto');

        if (empty($dealID)) {
            $this->logger->critical("Deal is Empty");
        }
        $repDeal = $this->getDoctrine()->getRepository(Deal::class);
        /** @var Deal $deal */
        $deal = $repDeal->findOneByDealid($dealID);

        if (empty($deal)) {
            return $this->redirect("/");
        }
        $affiliateLink = $deal->getAffiliatelink();

        switch ($targetUrl) {
            case 'Deals':
                $targetUrl = $deal->getDealsLink();

                break;

            case 'Affiliate':
                $targetUrl = $deal->getAffiliateLink();

                break;

            case 'Link':
                $targetUrl = $deal->getLink();

                break;

            case 'DeepLink':
                $targetUrl = $this->router->generate("aw_account_redirect", ["ID" => intval($request->query->get('AccountID')), "Goto" => $deal->getLink()]);

                break;

            case 'DeepDealsLink':
                $targetUrl = $this->router->generate("aw_account_redirect", ["ID" => intval($request->query->get('AccountID')), "Goto" => $deal->getDealsLink()]);

                break;

            case 'AutoLogin':
                $targetUrl = $this->router->generate("aw_account_redirect", ["ID" => intval($request->query->get('AccountID'))]);

                break;

            default:
                return $this->redirect("/");
        }

        return $this->forward('AwardWallet\MainBundle\Controller\RedirectController::partnerAction', [
            'request' => $request,
            'targetUrl' => htmlspecialchars_decode($targetUrl),
            'preloadUrl' => htmlspecialchars_decode($affiliateLink),
        ]);
    }

    public function markDealPre(Request $request, $action)
    {
        // incoming params
        $dealID = intval($request->request->get('dealID'));
        $status = intval($request->request->get('status'));
        // result schema
        $resultResponse = [
            'error' => '',
            'content' => '',
        ];

        if (empty($dealID)) {
            $this->logger->critical("DealID is empty");
            $resultResponse['error'] = "Invalid Request";
        }

        if ($this->authorizationChecker->isGranted('ROLE_USER')) {
            // mark
            $userID = $this->tokenStorage->getBusinessUser()->getUserid();
            $this->markDeal($dealID, $userID, $status, $action);
            $resultResponse['content'] = 'OK';
        } else {
            $resultResponse['error'] = "You are not logged in";
        }

        return $this->render('@AwardWalletMain/content.json.twig', [
            'response' => $resultResponse,
        ]);
    }

    public static function getTranslationMessages()
    {
        return [
            (new Message('Register', 'promotions'))->setDesc('Register'),
        ];
    }

    protected function markDeal($dealID, $userID, $status, $field)
    {
        $repDealMark = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Dealmark::class);
        $repDeal = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Deal::class);
        $dealMarkItem = $repDealMark->findOneBy(['dealid' => $dealID, 'userid' => $userID]);

        if ($status == 0) {
            if (empty($dealMarkItem)) {
                $dealMark = new Dealmark();
                $deal = new Deal();
                $dealMark->setDealid($repDeal->findOneByDealid($dealID));
                $dealMark->setUserid($this->tokenStorage->getBusinessUser());

                switch ($field) {
                    case "Readed":
                        $dealMark->setReaded(1);
                        $dealMark->setFollow(0);
                        $dealMark->setApplied(0);
                        $dealMark->setManual(0);

                        break;

                    case "Follow":
                        $dealMark->setReaded(0);
                        $dealMark->setFollow(1);
                        $dealMark->setApplied(0);
                        $dealMark->setManual(0);

                        break;

                    case "Applied":
                        $dealMark->setReaded(0);
                        $dealMark->setFollow(0);
                        $dealMark->setApplied(1);
                        $dealMark->setManual(0);

                        break;

                    case "Manual":
                        $dealMark->setReaded(0);
                        $dealMark->setFollow(0);
                        $dealMark->setApplied(0);
                        $dealMark->setManual(1);

                        break;
                }

                // mark
                $this->entityManager->persist($dealMark);
                $this->entityManager->flush();
            } else {
                // unmark
                switch ($field) {
                    case "Readed":  $dealMarkItem->setReaded(1);

                        break;

                    case "Follow":  $dealMarkItem->setFollow(1);

                        break;

                    case "Applied": $dealMarkItem->setApplied(1);

                        break;

                    case "Manual":  $dealMarkItem->setManual(1);

                        break;
                }
                $this->entityManager->flush();
            }
        } else {
            if (!empty($dealMarkItem)) {
                // unread
                switch ($field) {
                    case "Readed":  $dealMarkItem->setReaded(0);

                        break;

                    case "Follow":  $dealMarkItem->setFollow(0);

                        break;

                    case "Applied": $dealMarkItem->setApplied(0);

                        break;

                    case "Manual":  $dealMarkItem->setManual(0);

                        break;
                }
                $this->entityManager->flush();
            }
        }
    }

    protected function updateTimesClicked($dealID)
    {
        $deal = $this->entityManager->getRepository(\AwardWallet\MainBundle\Entity\Deal::class)->find($dealID);

        if (!$deal) {
            $this->logger->warning('Deal not found', ["DealID" => $dealID]);

            return;
        }
        $deal->setTimesclicked($deal->getTimesclicked() + 1);
        $this->entityManager->flush();
    }
}
