<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Alliance;
use AwardWallet\MainBundle\Entity\Elitelevel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\OfferManager;
use AwardWallet\MainBundle\Security\LoginRedirector;
use AwardWallet\MainBundle\Service\Account\Export;
use AwardWallet\MainBundle\Service\Account\SearchHintsHelper;
use AwardWallet\MainBundle\Service\Billing\PlusManager;
use AwardWallet\MainBundle\Service\Blog\BlogPostInterface;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Counter;
use AwardWallet\MainBundle\Service\CreditCards\Advertise;
use AwardWallet\MainBundle\Service\PageVisitLogger;
use AwardWallet\MainBundle\Service\SocksMessaging\ClientInterface as SocksMessagingClientInterface;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ListController extends AbstractController
{
    private AwTokenStorageInterface $tokenStorage;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;

    public function __construct(
        AwTokenStorageInterface $tokenStorage,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
    }

    /**
     * @Route("/list/analysis", name="aw_spent_analysis_compability")
     * @Route("/list/analysis{params}", name="aw_spent_analysis_compability_html5", requirements={"params"=".+"})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @return RedirectResponse
     */
    public function spentAnalysisRedirectAction(Request $request)
    {
        return new RedirectResponse($this->generateUrl('aw_spent_analysis_index', $request->query->all()));
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/list/", name="aw_account_list", options={"expose"=true})
     * @Route("/list{params}", name="aw_account_list_html5", requirements={"params"="^[^\.]*$"}, options={"expose"=false})
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function listAction(
        Environment $twigEnv,
        Request $request,
        LoginRedirector $loginRedirector,
        \Memcached $memcached,
        Counter $counter,
        OfferManager $offerManager,
        AuthorizationCheckerInterface $authorizationChecker,
        BlogPostInterface $blogPost,
        SocksMessagingClientInterface $socksMessagingClient,
        Advertise $advertise,
        SearchHintsHelper $searchHintsHelper,
        CacheManager $cacheManager,
        $vapidPublicKey,
        $webpushIdParam,
        PageVisitLogger $pageVisitLogger
    ) {
        if ($url = $loginRedirector->getAccountListRedirect()) {
            return $this->redirect($url);
        }

        $twigEnv->addGlobal('webpack', true);

        // debug refs #23912
        $cacheManager
            ->setDebugMode($this->tokenStorage->getUser()->getId() === 531615);

        $cnt = $counter->getTotalAccounts($this->tokenStorage->getBusinessUser()->getUserid());
        $isPartial = false;

        $template = '@AwardWalletMain/Account/List/list.html.twig';

        $limit = $request->get('limit', false);

        if (!$limit) {
            $accountsLimit = in_array($this->tokenStorage->getBusinessUser()->getUserid(), $GLOBALS['eliteUsers']) ? false : PERSONAL_INTERFACE_MAX_ACCOUNTS;
        } else {
            $accountsLimit = PERSONAL_INTERFACE_MAX_ACCOUNTS;
        }

        $accountsData = $accounts = $this->getAccountsData($isPartial);
        unset($accountsData['rawAccounts']);

        if (
            $request->query->has('previewUserOfferId')
            && $authorizationChecker->isGranted('USER_IMPERSONATED')
        ) {
            $userOfferId = $request->query->get('previewUserOfferId');
        } else {
            $userOfferId = $offerManager->checkUserOffers($this->tokenStorage->getToken()->getUser(), $request);
        }

        if ($userOfferId) {
            $offerData = $offerManager->getOfferData($userOfferId, $request, $this->tokenStorage->getToken()->getUser());
        }

        $templateParams = [
            'accountsData' => $accountsData,
            // 'pending' => $this->getPendingAccounts(),
            'limit' => $accountsLimit,
            'partial' => $isPartial,
            'total' => $cnt,
            'offerData' => $offerData ?? null,
            'blogpost' => $blogPost->fetchLastPost(1),
            'centrifuge_config' => $socksMessagingClient->getClientData(),
            'adsData' => $advertise->getListByUser($this->tokenStorage->getToken()->getUser()),
            'vapid_public_key' => $vapidPublicKey,
            'webpush_id' => $webpushIdParam,
            'upgrade_popup_open' => $request->query->getBoolean('upgrade_popup', false),
            'grab_fingerprints' => $memcached->add('grab_fingerprints_' . $this->getUser()->getId(), time(), 86400),
            'search_hints' => $searchHintsHelper->getData($accounts),
        ];

        $templateParams = $this->addUpgradePopupParams($request, $templateParams);
        $pageVisitLogger->log(PageVisitLogger::PAGE_ACCOUNT_LIST);

        return $this->render($template, $templateParams);
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/data", name="aw_account_data", methods={"POST"}, options={"expose"=true})
     * @return JsonResponse
     */
    public function dataAction(RequestStack $requestStack)
    {
        $requests = json_decode($requestStack->getCurrentRequest()->getContent(), true);

        if (empty($requests)) {
            throw new BadRequestHttpException('Empty requests');
        }

        $ret = [];
        $userObject = $this->tokenStorage->getBusinessUser();

        foreach ($requests as $id => $request) {
            if ($request['dataset'] == 'accounts') {
                $optionsOld = $request['options'];
                $options = new Options();

                // TODO: filter valid options
                foreach ($optionsOld as $optionName => $optionLevel) {
                    $options->set($optionName, $optionLevel);
                }

                $accountList = $this->accountListManager
                    ->getAccountList(
                        $this->optionsFactory->createDesktopListOptions($options)
                            ->set(Options::OPTION_USER, $userObject)
                    );
                $accounts = $accountList->getAccounts();
                $total = $accountList->getAccountsCount();

                $ret[] = [
                    'id' => $id,
                    'result' => $accounts,
                    'total' => $total,
                ];
            } elseif ($request['dataset'] == 'agents') {
                $agents = $this->accountListManager->getAgentsInfo($userObject);

                $ret[] = [
                    'id' => $id,
                    'result' => $agents,
                ];
            } elseif ($request['dataset'] == 'kinds') {
                $kinds = $this->accountListManager->getProviderKindsInfo();

                $ret[] = [
                    'id' => $id,
                    'result' => $kinds,
                ];
            } elseif ($request['dataset'] == 'user') {
                $user = $this->accountListManager->getUserInfo($userObject);

                $ret[] = [
                    'id' => $id,
                    'result' => $user,
                ];
            }
        }

        return (new JsonResponse($ret))->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_SITE_BUSINESS_AREA')")
     * @Route("/data/alliance/{alias}", name="aw_account_list_alliance_info", options={"expose"=true})
     * @Template("@AwardWalletMain/AccountList/_alliancePopup.html.twig")
     */
    public function allianceInfoAction(
        $alias,
        Request $request,
        AccountListManager $accountListManager,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $awTokenStorage
    ): array {
        $scale = $request->query->getInt('scale', 1);

        if (empty($alias)) {
            throw $this->createNotFoundException();
        }

        $alliance = $entityManager->getRepository(Alliance::class)->findOneBy(['alias' => $alias]);

        if (empty($alliance)) {
            throw $this->createNotFoundException();
        }

        $accounts = $accountListManager->getAccountList(
            $this->optionsFactory->createDesktopListOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $awTokenStorage->getBusinessUser())
                    ->set(Options::OPTION_ALLIANCEID, $alliance->getAllianceid())
                    ->set(Options::OPTION_ORDER, 'ProviderName')
                    ->set(Options::OPTION_AS_OBJECT, false)
            )
        )->getAccounts();
        $agents = $accountListManager->getAgentsInfo($awTokenStorage->getBusinessUser());

        $providers = $entityManager->getConnection()->fetchAllAssociative('
            SELECT p.ProviderID, p.ShortName, p.IATACode
            FROM Provider p
            JOIN Airline a ON (a.Code = p.IATACode)
            WHERE
                    a.AllianceID = ?
                AND p.Corporate = 0
            GROUP BY p.ProviderID, a.AllianceID
            ORDER BY p.ProgramName ASC',
            [$alliance->getAllianceid()],
            [\PDO::PARAM_INT]
        );
        $providers = array_column($providers, null, 'ProviderID');

        $addedAirlines = $otherAirlines = [];

        foreach ($accounts as $account) {
            $providerId = (int) $account['ProviderID'];

            if (!array_key_exists($providerId, $providers)) {
                continue;
            }

            if (!array_key_exists($providerId, $addedAirlines)) {
                $addedAirlines[$providerId] = [
                    'ShortName' => $providers[$providerId]['ShortName'],
                    'IATACode' => $providers[$providerId]['IATACode'],
                    'accounts' => [],
                ];
            }

            $account['UserName'] = current(array_filter(
                $agents,
                static fn ($a) => $a['ID'] === $account['AccountOwner']
            ))['name'];
            $account['AllianceIcon'] = str_replace('alliances/', 'alliances/old/', $account['AllianceIcon']);
            $account['AllianceIcon'] .= (2 === $scale ? '@2x' : '') . '.png';

            if (!empty($account['AccountStatus'])) {
                $el = $entityManager->getRepository(Elitelevel::class)
                    ->getEliteLevelFields($providerId, $account['AccountStatus']);

                if ($el) {
                    $account['AllianceLevel'] = $el['AllianceName'];
                }
            } else {
                $account['AccountStatus'] = $account['AllianceLevel'] = '';
            }

            $addedAirlines[$providerId]['accounts'][] = $account;
        }

        foreach ($providers as $providerId => $provider) {
            if (array_key_exists($providerId, $addedAirlines)) {
                continue;
            }

            $otherAirlines[] = $provider;
        }

        if (!empty($providers)) {
            $otherAllianceProviders = $entityManager->getConnection()->fetchAllAssociative('
                SELECT ProviderID, IATACode, ShortName
                FROM Provider
                WHERE
                        ProviderID NOT IN(?)    
                    AND AllianceID = ? 
                    AND Corporate = 0',
                [array_keys($providers), $alliance->getAllianceid()],
                [Connection::PARAM_INT_ARRAY, \PDO::PARAM_INT]
            );
            $otherAirlines = array_merge($otherAirlines, $otherAllianceProviders);
        }

        array_multisort(array_column($otherAirlines, 'ShortName'), SORT_ASC, $otherAirlines);

        return [
            'addedAirlines' => $addedAirlines,
            'otherAirlines' => $otherAirlines,
        ];
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/export/{type}.{format}", name="aw_account_export_type", options={"expose"=true})
     * @Template("@AwardWalletMain/Account/List/export.html.twig")
     */
    public function exportAction(Request $request, TranslatorInterface $translator, Export $accountExport)
    {
        $type = $request->get('type');
        $format = $request->get('format');

        if (!$this->getUser()->isAwPlus()) {
            return [
                'needUpgrade' => true,
                'title' => $translator->trans('please-upgrade'),
                'message' => $translator->trans('award.account.popup.need-upgrade.p2'),
            ];
        }

        switch ($type) {
            case 'travelPlanner':
                $data = $accountExport->setUser($this->getUser())->travelPlanner($format);

                if (is_string($data)) {
                    return [
                        'title' => $translator->trans('error.award.account.other.title'),
                        'message' => $data,
                    ];
                }

                $slugify = new \Cocur\Slugify\Slugify();
                $userName = ucfirst($slugify->slugify($this->getUser()->getFirstname())) . ' ' . ucfirst($slugify->slugify($this->getUser()->getLastname()));

                if (!$result = $accountExport->downloadExcel($data, ['fileName' => 'AwardWallet.com - Travel Planner for ' . trim($userName) . '.xls'])) {
                    throw $this->createNotFoundException();
                }

                if ($result instanceof Response) {
                    return $result;
                }

                break;
        }

        return [
            'title' => $translator->trans('error.award.account.other.title'),
            'message' => $translator->trans('account.export.no-data'),
        ];
    }

    private function getAccountsData($isPartial = false)
    {
        $accounts = [];
        $userObject = $this->tokenStorage->getBusinessUser();

        if (!$isPartial) {
            $accountList = $this->accountListManager->getAccountList(
                $this->optionsFactory->createDesktopListOptions(
                    (new Options())
                        ->set(Options::OPTION_USER, $userObject)
                        ->set(Options::OPTION_LOAD_MILE_VALUE, true)
                        ->set(Options::OPTION_LOAD_BLOG_POSTS, true)
                )
            );
            $accounts = $accountList->getAccounts();
            $rawAccounts = $accountList->getRawAccounts();
        }

        return [
            'accounts' => $accounts,
            'kinds' => $this->accountListManager->getProviderKindsInfo(),
            'agents' => $this->accountListManager->getAgentsInfo($userObject),
            'user' => $this->accountListManager->getUserInfo($userObject),
            'rawAccounts' => $rawAccounts ?? [],
        ];
    }

    private function addUpgradePopupParams(Request $request, array $templateParams): array
    {
        if (!empty($templateParams['offerData']['Code'])
            && 'awardwalletplussubscription' === $templateParams['offerData']['Code']) {
            return $templateParams;
        }

        $user = $this->tokenStorage->getToken()->getUser();

        if (true === $request->getSession()->get(PlusManager::SESSION_KEY_SHOW_UPGRADE_POPUP)) {
            $templateParams['user'] = [
                'name' => $user->getFirstname(),
            ];

            $templateParams['upgradeNotifyPopup'] = ['autoOpen' => true];
        }

        return $templateParams;
    }
}
