<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Configuration\JsonDecode;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Factory\AccountFactory;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\Handler;
use AwardWallet\MainBundle\Form\Type\AccountType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Manager\LogoManager;
use AwardWallet\MainBundle\Security\PasswordChecker;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\MainBundle\Service\AccountCounter\Counter;
use AwardWallet\MainBundle\Service\PopularityHandler;
use AwardWallet\MainBundle\Service\ProviderRating;
use AwardWallet\MainBundle\Worker\AsyncProcess\Process;
use AwardWallet\MainBundle\Worker\AsyncProcess\StoreLocationFinderTask;
use Doctrine\DBAL\Driver\Connection as ConnectionInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class EditController extends AbstractController
{
    private AuthorizationCheckerInterface $authorizationChecker;
    private EntityManagerInterface $entityManager;
    private AwTokenStorageInterface $tokenStorage;
    private LoggerInterface $logger;
    private LogoManager $logoManager;
    private ConnectionInterface $connection;
    private AccountManager $accountManager;
    private RouterInterface $router;
    private LocalPasswordsManager $localPasswordsManager;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private DesktopListMapper $desktopListMapper;
    private Handler $handlerDesktop;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        EntityManagerInterface $entityManager,
        AwTokenStorageInterface $tokenStorage,
        LoggerInterface $logger,
        LogoManager $logoManager,
        ConnectionInterface $connection,
        AccountManager $accountManager,
        RouterInterface $router,
        LocalPasswordsManager $localPasswordsManager,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        DesktopListMapper $desktopListMapper,
        Handler $accountHandlerDesktop
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->entityManager = $entityManager;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->logoManager = $logoManager;
        $this->connection = $connection;
        $this->accountManager = $accountManager;
        $this->router = $router;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->desktopListMapper = $desktopListMapper;
        $this->handlerDesktop = $accountHandlerDesktop;
    }

    /**
     * @Security("is_granted('ROLE_USER')")
     * @Route("/add/{providerId}", name="aw_account_add", options={"expose"=true}, defaults={"providerId" = null})
     * @ParamConverter("provider", class="AwardWalletMainBundle:Provider", options={"id" = "providerId"})
     */
    public function addAction(
        Request $request,
        ?Provider $provider = null,
        ProviderRating $ratingLoyaltyProgram,
        Process $process,
        AccountFactory $accountFactory
    ) {
        if ($provider && !$this->authorizationChecker->isGranted('ADD', $provider)) {
            throw $this->createAccessDeniedException();
        }
        $user = $this->tokenStorage->getBusinessUser();
        $account = $accountFactory->create();
        $account->setUser($user);
        $account->setProviderid($provider);
        $savePassword = isset($provider) && $provider->getPasswordrequired()
            ? $user->getSavepassword() : SAVE_PASSWORD_DATABASE;

        if (!empty($provider) && $provider->getCode() == 'aa') {
            $savePassword = SAVE_PASSWORD_LOCALLY;
        }
        $account->setSavepassword($savePassword);

        $agentId = $request->query->get('agentId');

        if (!empty($agentId) && $agentId != 'my') {
            $agent = $this->entityManager->getRepository(Useragent::class)->find($agentId);

            if (empty($agent) || !$this->authorizationChecker->isGranted('EDIT_ACCOUNTS', $agent)) {
                throw $this->createAccessDeniedException();
            }

            if (!empty($agent->getClientid())) {
                $account->setUser($agent->getClientid());
                $account->getUseragents()->add($agent);
            } else {
                $account->setUserAgent($agent);
            }
        }

        $response = $this->editAction($request, $account, $ratingLoyaltyProgram, $this->entityManager);

        if ($task = StoreLocationFinderTask::createFromLoyalty($account)) {
            $process->execute($task);
        }

        return $response;
    }

    /**
     * access rights checked inside form type: AccountGeneric::preHandle, and doubled here.
     *
     * @Security("is_granted('ROLE_USER')")
     * @Route("/edit/{accountId}", name="aw_account_edit", options={"expose"=true})
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id" = "accountId"})
     */
    public function editAction(
        Request $request,
        Account $account,
        ProviderRating $ratingLoyaltyProgram,
        EntityManagerInterface $entityManager
    ) {
        if (!$this->authorizationChecker->isGranted('EDIT', $account)) {
            throw $this->createAccessDeniedException();
        }

        // logo
        $session = $request->getSession();

        if ($request->query->has('AbRequestID') || $session->has('AbRequestID')) {
            $requestId = $request->query->get('AbRequestID', $session->get('AbRequestID'));

            if (!empty($requestId) && is_scalar($requestId)) {
                $abRequest = $this->getDoctrine()->getRepository(\AwardWallet\MainBundle\Entity\AbRequest::class)->find($requestId);

                if ($abRequest && $this->authorizationChecker->isGranted("VIEW", $abRequest)) {
                    $session->set('AbRequestID', $requestId); // @FIXME: bug, now all account edits will show booking logo
                    $this->logoManager->setBookingRequest($abRequest);
                }
            }
        }

        if (ACCOUNT_PENDING === $account->getState() && empty($account->getLogin())) {
            if (!empty($account->getAccountNumber())) {
                $account->setLogin($account->getAccountNumber());
            } elseif (!empty($account->getSourceEmail())
                && $account->getProviderid()
                && false !== stripos($account->getProviderid()->getLogincaption(), 'email')
            ) {
                $account->setLogin($account->getSourceEmail());
            }
        }

        $form = $this->createForm(AccountType::class, $account);

        $provider = $account->getProviderid();

        if ($provider && !$this->authorizationChecker->isGranted('ADD', $provider)) {
            throw $this->createAccessDeniedException();
        }

        $checkInBrowser = false;
        $isOauthProvider = false;

        if ($provider) {
            $checkInBrowser = $provider->getCheckinbrowser();

            if ($session->has('DisableExtension') || $request->cookies->get('SB') == 'false') {
                $checkInBrowser = CHECK_IN_SERVER;
            }
            $isOauthProvider = $provider->isOauthProvider();
        }

        $accountData = null;
        $autosubmit = $request->get('autosubmit', false);

        if ($autosubmit) {
            $data = [];

            // todo move to recursive helper
            foreach ($form as $key => $value) {
                $data[$key] = $value->getViewData();
            }
            $view = $form->createView();
            $data['_token'] = $view['_token']->vars['value'];

            $request->request->replace([
                $form->getName() => $data,
            ]);

            $request->setMethod('POST');

            $check = $account->canCheck() && !$account->isDisabled();
        } else {
            $check = false;
        }

        if ($this->authorizationChecker->isGranted('UPDATE', $account) && $account->getErrorcode() == ACCOUNT_UNCHECKED
            && !($isOauthProvider && !$account->getAuthInfo() && !$account->getPass())
        ) {
            $check = true;
        }

        $this->connection->beginTransaction();

        try {
            if ($this->handlerDesktop->handleRequest($form, $request)) {
                $this->_checkSymbols($account, $form, $request->headers->get('User-Agent')); // refs #15885

                $this->entityManager->persist($account);
                $this->entityManager->flush();

                $this->accountManager->storeLocalPasswords($this->getUser());

                $this->connection->commit();

                if (((!$account->credentialsChanged && !$account->disabledChanged) || !$this->authorizationChecker->isGranted('UPDATE', $account) || $account->isDisabled()) && !$check) {
                    if ($request->query->has("backTo")) {
                        return $this->redirect($request->getSchemeAndHttpHost() . $request->query->get("backTo"));
                    } else {
                        $params['account'] = $account->getAccountid();

                        if ($account->getIsArchived()) {
                            $params['archive'] = 'on';
                        }

                        return $this->redirect($this->router->generate('aw_account_list', $params));
                    }
                }
                $check = true;

                if ($account->getAccountid()) {
                    $this->entityManager->refresh($account);
                    $form = $this->createForm(AccountType::class, $account);
                }
            } else {
                $check = !empty($request->query->get("check"));
                $this->connection->rollBack();
            }
        } catch (\Exception $e) {
            if ($this->connection->isTransactionActive()) {
                $this->connection->rollback();
            }
            $this->localPasswordsManager->clear();

            throw $e;
        }

        if ($check) {
            $accounts = $this->accountListManager
                ->getAccountList(
                    $this->optionsFactory
                        ->createDefaultOptions()
                        ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
                        ->set(Options::OPTION_FILTER, " AND a.AccountID = " . $account->getAccountid())
                        ->set(Options::OPTION_COUPON_FILTER, ' AND 0 = 1')
                        ->set(Options::OPTION_FORMATTER, $this->desktopListMapper)
                )
                ->getAccounts();

            foreach ($accounts as $value) {
                if ($value['ID'] == $account->getAccountid()) {
                    $accountData = $value;
                }
            }

            if (empty($accountData)) {
                $check = false;
            }
        }

        return $this->render('@AwardWalletMain/Account/Add/edit.html.twig', [
            'account' => $account,
            'isOauthProvider' => $isOauthProvider,
            'isOauthTokenExist' => (bool) $account->getAuthInfo(),
            'accountData' => $accountData,
            'check' => $check,
            'form' => $form->createView(),
            'checkInBrowser' => $checkInBrowser,
            'displayName' => preg_replace('/\((.*?)\)/', '<span>(\\1)</span>', $form->getConfig()->getAttribute('header')),
            'successRate' => $account->canCheck() ? $this->entityManager->getRepository(Provider::class)->getSuccessRateProvider($account->getProviderid()->getProviderid()) : null,
            'rating' => $account->getProviderid() ? $ratingLoyaltyProgram->getReviewData($account->getProviderid()->getId()) : [],
            'ratingUrl' => $account->getProviderid() ? ProviderRating::urlName($account->getProviderid()->getDisplayname()) : '',
        ]);
    }

    /**
     * @Security("is_granted('SITE_BUSINESS_AREA') and is_granted('ROLE_USER')")
     * @Route("/get-owners", name="aw_account_get_owners", options={"expose"=true})
     * @return JsonResponse
     */
    public function getMembersAction(Request $request, Counter $accountCounter)
    {
        $query = $request->get('q');
        $full = $request->get('full', false);

        if (!is_string($query) || !is_scalar($full)) {
            throw $this->createNotFoundException();
        }

        $user = $this->tokenStorage->getBusinessUser();
        $query = trim($query);
        $result = [];

        if (stripos($user->getFullName(), $query) !== false) {
            $accountSummary = $accountCounter->calculate($user->getId());

            $result[] = [
                'value' => 'my',
                'label' => $user->getFullName(),
                'name' => $user->getFullName(),
                'count' => $accountSummary->getCount(0),
            ];
        }

        $members = $this->entityManager->getRepository(Useragent::class)->getBusinessMembersData($user, $query, 10);

        foreach ($members as $member) {
            if (
                !isset($member['LinkUserAgentID'])
                || (!$member['IsApproved'] && $member['ClientID'])
            ) {
                continue;
            }
            $result[] = [
                'value' => $member['LinkUserAgentID'],
                'count' => $member['Programs'],
                'label' => $member['Name'],
                'name' => $member['Name'],
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * @Security("is_granted('SITE_BUSINESS_AREA') and is_granted('ROLE_USER')")
     * @Route("/get-programs", name="aw_account_get_programs", options={"expose"=true})
     * @return JsonResponse
     */
    public function getProgramsAction(Request $request, PopularityHandler $popularityHandler)
    {
        $query = $request->get('q');

        if (!is_string($query)) {
            throw $this->createNotFoundException();
        }

        $providers = $popularityHandler->getPopularPrograms();

        $query = strtolower(trim($query));
        $result = [];

        if ($query) {
            $providers = array_filter($providers, function ($provider) use ($query) {
                return strpos(strtolower($provider['DisplayName']), $query) !== false;
            });

            $result = [];

            foreach ($providers as $provider) {
                $result[] = [
                    'value' => $provider['ProviderID'],
                    'label' => $provider['DisplayName'],
                ];
            }
            $result = array_slice($result, 0, 10);
        }

        return new JsonResponse($result);
    }

    /**
     * @Route("/autologin-enable/{accountId}", name="aw_account_autologin_enable", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('CSRF') and is_granted('READ_PASSWORD', account)")
     * @ParamConverter("account", class="AwardWalletMainBundle:Account", options={"id"="accountId"})
     * @JsonDecode
     */
    public function autologinEnable(
        Request $request,
        Account $account,
        ReauthenticatorWrapper $reauthenticator,
        PasswordChecker $passwordChecker,
        TranslatorInterface $translator
    ) {
        $action = Action::getEnableAutoLoginAction($account->getAccountid());
        $password = $request->get('password');

        if (!$reauthenticator->isReauthenticated($action) && !$passwordChecker->checkPasswordSafe($this->tokenStorage->getToken()->getUser(), $password, $request->getClientIp(), $lockoutError)) {
            return new JsonResponse([
                'success' => false,
                'error' => $lockoutError ?? $translator->trans('invalid.password', [], 'validators'),
            ]);
        }

        $account->setDisableClientPasswordAccess(false);
        $this->entityManager->persist($account);
        $this->entityManager->flush();
        $reauthenticator->reset($action);

        return new JsonResponse([
            'success' => true,
        ]);
    }

    private function _checkSymbols(Account $account, $form, $ua)
    {
        if (empty($form)) {
            return;
        }

        $unicode2Entity = function ($string) {
            $entityNum = array_merge(\range(8192, 8207), \range(8234, 8239), \range(8287, 8303));

            return preg_replace_callback("/([\340-\357])([\200-\277])([\200-\277])/", function ($matches) use ($entityNum) {
                $code = (\ord($matches[1]) - 224) * 4096 + (\ord($matches[2]) - 128) * 64 + (\ord($matches[3]) - 128);

                if (\in_array($code, $entityNum, true)) {
                    return '';
                }

                return $matches[0];
            }, $string);
        };

        $log = [
            'UserID' => $account->getUserid()->getUserid(),
            'AccountID' => $account->getAccountid(),
            'UserAgent' => $ua,
        ];
        $mask = ['⓪', '⓪', '①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬', '⑭', '⑮', '⑯', '⑰', '⑱', '⑲', '⑳', '➀', '➁', '➂', '➃', '➄', '➅', '➆', '➇', '➈', '➉', '⓿', '❶', '❷', '❸', '❹', '❺', '❻', '❼', '❽', '❾', '❿', '➊', '➋', '➌', '➍', '➎', '➏', '➐', '➑', '➒', '➓', '⓫', '⓬', '⓭', '⓮', '⓯', '⓰', '⓱', '⓲', '⓳', '⓴'];

        if ($form->has('pass') && !empty($pass = $form->get('pass')->getData())) {
            $cleanPass = str_replace($mask, '', $pass);

            if (0 === mb_strlen($cleanPass) && mb_strlen($pass) !== mb_strlen($cleanPass)) {
                $this->logger->warning($msg = 'Strange symbol in account PASSWORD field (change event: ' . ($_COOKIE['pass' . $account->getId()] ?? '-') . ')', $log);
                $found[] = $msg;
            }
        }

        foreach (['login', 'login2', 'login3'] as $fieldName) {
            if (!$form->has($fieldName) || empty($value = $form->get($fieldName)->getData())) {
                continue;
            }

            $value instanceof \DateTimeInterface ? $value = $value->format('Y-m-d') : null;
            $cleaned = str_replace($mask, '', $value);
            $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleaned);
            $cleaned = $unicode2Entity($cleaned);

            if (mb_strlen($cleaned) !== mb_strlen($value)) {
                $this->logger->warning($msg = 'Strange symbol in account ' . strtoupper($fieldName) . ' field (change event: ' . ($_COOKIE['pass' . $account->getId()] ?? '-') . ')', $log);
                $found[] = $msg;
            }

            if (is_string($value) && (strpos($value, '****') === 0 || strpos($value, '****') === strlen($value) - 4)) {
                $this->logger->warning($msg = 'Possibly masked value in account ' . strtoupper($fieldName) . ' field (change event: ' . ($_COOKIE['pass' . $account->getId()] ?? '-') . ')', $log);
                $found[] = $msg;
            }
        }

        /*if (!empty($found)) {
            $message = $this->getMailMessageTo('notifications');
            $message
                ->setSubject('[Dev Notification]: Strange symbol in fields refs #15885')
                ->setFrom(['info@awardwallet.com' => 'AwardWallet'])
                ->setBody(implode("\n", $found) . "\n\r" . var_export($log, true), 'text/plain');
            $this->getSender()->send($message);
        }*/
    }
}
