<?php

namespace AwardWallet\MainBundle\Controller\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\AccountRepository;
use AwardWallet\MainBundle\Entity\Repositories\AccountshareRepository;
use AwardWallet\MainBundle\Entity\Repositories\ProvidercouponshareRepository;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Event\AccountChangedEvent;
use AwardWallet\MainBundle\Form\Type\AccountList\ChangeAccountArchiveType;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\AccessLevel\AccessControlList;
use AwardWallet\MainBundle\Globals\AccountList\Mapper\DesktopListMapper;
use AwardWallet\MainBundle\Globals\AccountList\Options;
use AwardWallet\MainBundle\Globals\AccountList\OptionsFactory;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Manager\AccountListManager;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\Exception\MalformedLocalPasswordsException;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Repository\ProvidercouponRepository;
use AwardWallet\MainBundle\Security\Reauthentication\Action;
use AwardWallet\MainBundle\Security\Reauthentication\ReauthenticatorWrapper;
use AwardWallet\MainBundle\Service\AccountCounter\Counter;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\BalanceWatch\Constants;
use AwardWallet\MainBundle\Service\BalanceWatch\Stopper;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\Valid;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route("/action")
 */
class ListActionController extends AbstractController
{
    public const FAILED_BACKUP_PATH = '/var/log/www/awardwallet';

    private EventDispatcherInterface $eventDispatcher;
    private AccountRepository $accountRepository;
    private ProvidercouponRepository $providerCouponRepository;
    private UseragentRepository $useragentRepository;
    private AccountshareRepository $accountShareRepository;
    private ProvidercouponshareRepository $providerCouponShareRepository;
    private AwTokenStorageInterface $tokenStorage;
    private TranslatorInterface $translator;
    private AccountManager $accountManager;
    private EntityManagerInterface $entityManager;
    private AccessControlList $accessControlList;
    private AccountListManager $accountListManager;
    private OptionsFactory $optionsFactory;
    private Logger $logger;
    private LocalPasswordsManager $localPasswordsManager;
    private RequestStack $requestStack;
    private DesktopListMapper $mapper;
    private AuthorizationCheckerInterface $authorizationChecker;
    private BackgroundCheckScheduler $checkScheduler;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        AccountRepository $accountRepository,
        ProvidercouponRepository $providerCouponRepository,
        UseragentRepository $useragentRepository,
        AccountshareRepository $accountShareRepository,
        ProvidercouponshareRepository $providerCouponShareRepository,
        AwTokenStorageInterface $tokenStorage,
        TranslatorInterface $translator,
        AccountManager $accountManager,
        EntityManagerInterface $entityManager,
        AccessControlList $accessControlList,
        AccountListManager $accountListManager,
        OptionsFactory $optionsFactory,
        Logger $logger,
        LocalPasswordsManager $localPasswordsManager,
        RequestStack $requestStack,
        DesktopListMapper $mapper,
        AuthorizationCheckerInterface $authorizationChecker,
        BackgroundCheckScheduler $checkScheduler
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->accountRepository = $accountRepository;
        $this->providerCouponRepository = $providerCouponRepository;
        $this->useragentRepository = $useragentRepository;
        $this->accountShareRepository = $accountShareRepository;
        $this->providerCouponShareRepository = $providerCouponShareRepository;
        $this->tokenStorage = $tokenStorage;
        $this->translator = $translator;
        $this->accountManager = $accountManager;
        $this->entityManager = $entityManager;
        $this->accessControlList = $accessControlList;
        $this->accountListManager = $accountListManager;
        $this->optionsFactory = $optionsFactory;
        $this->logger = $logger;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->requestStack = $requestStack;
        $this->mapper = $mapper;
        $this->authorizationChecker = $authorizationChecker;
        $this->checkScheduler = $checkScheduler;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/assign",
     *      name="aw_account_assign_owner",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function assignOwnerAction(Request $request, Counter $accountCounter)
    {
        global $eliteUsers;

        $currentUser = $this->tokenStorage->getBusinessUser();
        $items = $request->request->get('accounts');
        $newOwnerID = $request->request->get('newOwner');
        $errorResponse = ['accounts' => [], 'error' => 'Invalid request'];

        if (!isset($items) || !is_array($items) || !sizeof($items) || !is_scalar($newOwnerID)) {
            return new JsonResponse($errorResponse);
        }
        $ua = null;

        if ($newOwnerID !== 'my') {
            $ua = $this->useragentRepository->find($newOwnerID);

            if (!$ua || $ua->getAgentid()->getUserid() != $currentUser->getUserid() || !$ua->isPossibleOwner()) {
                return new JsonResponse($errorResponse);
            }
            $newUser = $ua->isFamilyMember() ? $currentUser : $ua->getClientid();
            $newUserId = $newUser->getUserid();
            $newUaId = $ua->isFamilyMember() ? $ua->getUseragentid() : 0;
            $newOwnerName = $ua->getFullName();
        } else {
            $newUser = $currentUser;
            $newUserId = $newUser->getUserid();
            $newUaId = 0;
            $newOwnerName = $newUser->getFullName();
        }

        $accountSummary = $accountCounter->calculate($newUserId);
        $newUserCountAccounts = $accountSummary->getCount();
        $newUserAgentCountAccounts = $accountSummary->getCount($newUaId);
        $newEliteUser = in_array($newUserId, $eliteUsers);
        $result = [];

        try {
            foreach ($items as $item) {
                if (isset($item[0], $item[1]) && is_numeric($item[0])) {
                    $account = null;

                    if ($item[1] === 'Account') {
                        $account = $this->accountRepository->find($item[0]);
                    } elseif ($item[1] === 'Coupon') {
                        $account = $this->providerCouponRepository->find($item[0]);
                    }

                    /** @var Account|Providercoupon $account */
                    if ($account && $this->isGranted('EDIT', $account)) {
                        $oldUserId = $account->getUserid()->getUserid();
                        $oldUaId = $account->getUseragentid() ? $account->getUseragentid()->getUseragentid() : 0;
                        $providerId = ($account instanceof Account && $account->getProviderid())
                            ? $account->getProviderid()->getProviderid() : null;
                        $isCustom = !isset($providerId);

                        if ($oldUserId == $newUserId && $oldUaId == $newUaId) {
                            continue;
                        }

                        if ($newUserId != $oldUserId && !$newUser->isBusiness() && !$newEliteUser) {
                            if ($newUserCountAccounts >= PERSONAL_INTERFACE_MAX_ACCOUNTS) {
                                throw new \LogicException($this->translator->trans('account.notice.account.personal-max-accounts.change-owner-group', ['%count%' => count($items), '%owner%' => $newOwnerName, '%limit%' => PERSONAL_INTERFACE_MAX_ACCOUNTS]));
                            }
                        }

                        if ($newUserAgentCountAccounts >= MAX_ACCOUNTS_PER_PERSON && !$newEliteUser) {
                            throw new \LogicException($this->translator->trans(/** @Desc("You are about to change the owner of selected accounts to the new name: %owner%, however this person already has %count% accounts added to his or her profile. Such transaction would put this person over the %limit% account limit. Under the current interface you should not have more than %limit% loyalty accounts per person.") */ 'account.notice.account.personal-max-per-person.change-owner-group', ['%owner%' => $newOwnerName, '%count%' => $newUserAgentCountAccounts, '%limit%' => MAX_ACCOUNTS_PER_PERSON]));
                        }

                        if ($account instanceof Account && !$isCustom) {
                            $countAccounts = $accountSummary->getCountAccountsByProviderIds([$providerId], $newUaId);

                            if ($countAccounts >= MAX_LIKE_LP_PER_PERSON && !$newEliteUser) {
                                throw new \LogicException($this->translator->trans(/** @Desc("Unfortunately you are not allowed to have more than %limit% accounts of the same provider listed under the same person. If we were to complete this operation %owner% would have %count% %providerName% accounts. Please select a different owner for these programs.") */ 'account.notice.account.max_like_lp_group', ['%owner%' => $newOwnerName, '%count%' => $countAccounts, '%providerName%' => $account->getProviderid()->getShortname(), '%limit%' => MAX_LIKE_LP_PER_PERSON]));
                            }
                        }

                        $em = $this->getDoctrine()->getManager();

                        $this->accountManager->setOwner($account, $newUser, $ua);
                        $em->flush();

                        $meOwner = $newUser->getId() === $currentUser->getId() && !$ua;
                        $moved = [
                            'owner' => $meOwner ? 'my' : $newOwnerID,
                        ];

                        if ($account instanceof Account) {
                            $moved['ID'] = $account->getAccountid();
                            $moved['FID'] = 'a' . $account->getAccountid();
                            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_ASSIGN_OWNER));
                            $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getAccountid()), AccountChangedEvent::NAME);
                        } else {
                            $moved['ID'] = $account->getProvidercouponid();
                            $moved['FID'] = 'c' . $account->getProvidercouponid();
                        }
                        $newUserCountAccounts++;
                        $newUserAgentCountAccounts++;
                        $result[] = $moved;
                    }
                }
            }
        } catch (\LogicException $e) {
            $error = $e->getMessage();
        }

        $response = ['accounts' => $result];

        if (isset($error)) {
            $response['error'] = $error;
        }

        return new JsonResponse($response);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/store-passwords",
     *      name="aw_account_store_passwords",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     * @throws MalformedLocalPasswordsException
     * @throws \Exception
     */
    public function accountStorePasswordsAction(Request $request, Stopper $bwStopper, LoggerInterface $paymentLogger)
    {
        $this->accessControlList->setUser($this->tokenStorage->getBusinessUser());
        $listOptions = $this->optionsFactory
            ->createDesktopListOptions(
                (new Options())
                    ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
                    ->set(Options::OPTION_LOAD_HAS_ACTIVE_TRIPS, true)
                    ->set(Options::OPTION_LOAD_PENDING_SCAN_DATA, true)
                    ->set(Options::OPTION_AS_OBJECT, false)
                    ->set(Options::OPTION_STATEFILTER, 'a.State > 0')
            );

        $ids = $request->request->get('accounts');

        if (!isset($ids) || !is_array($ids) || !sizeof($ids)) {
            throw $this->createNotFoundException();
        }

        $storage = $request->request->get('storage');

        if ($storage == 'database') {
            $storage = SAVE_PASSWORD_DATABASE;
        } else {
            if ($storage == 'local') {
                $storage = SAVE_PASSWORD_LOCALLY;
            } else {
                return new Response("Bad request", 400);
            }
        }

        /** @var Account[] $changedAccounts */
        $changedAccounts = [];

        foreach ($ids as $id) {
            $id = intval(preg_replace('/[^0-9]/', '', $id));

            if (!$this->accessControlList->toAccount()->allow($id, 'edit')) {
                continue;
            }
            /** @var Account $account */
            $account = $this->accountRepository->find($id);
            $this->accountManager->setAccountStorage($account, $account->getSavepassword(), $storage);
            $this->entityManager->persist($account);
            $changedAccounts[] = $account;

            if (SAVE_PASSWORD_LOCALLY === $storage
                && null !== $account->getBalanceWatchStartDate()
                && !in_array($account->getProviderid()->getId(), BalanceWatchManager::EXCLUDED_PROVIDER_LOCAL_PASSWORD)
            ) {
                $paymentLogger->info('BalanceWatchManager - password storage change to local, BalanceWatch STOP', ['AccountID' => $account->getId()]);
                $this->logger->info('BalanceWatch - STOP', ['accountId' => $account->getId(), 'place' => 'ListActionController::accountStorePasswordsAction']);
                $bwStopper->stopBalanceWatch($account, Constants::EVENT_FORCED_STOP);
            }
        }
        $this->entityManager->flush();

        foreach ($changedAccounts as $account) {
            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_STORE_PASSWORD));
            $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getId()), AccountChangedEvent::NAME);
        }

        $accounts = [];

        foreach ($ids as $id) {
            $accounts[$id] = $this->accountListManager->getAccount($listOptions, intval(preg_replace('/[^0-9]/', '', $id)), false);
        }

        $response = new JsonResponse($accounts);

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/share",
     *      name="aw_account_share",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function shareAction(Request $request)
    {
        $items = $request->request->get('accounts');
        $shares = $request->request->get('shares');

        $errorResponse = ['accounts' => [], 'error' => 'Invalid request'];

        if (!isset($items) || !is_array($items) || !sizeof($items) || !is_array($shares)) {
            return new JsonResponse($errorResponse);
        }

        $shares = array_filter($shares, function ($v) {return $v != 1; }); // remove "store as-is" share type
        $userAgents = $this->useragentRepository->findBy(['useragentid' => array_keys($shares)]);

        $ids = [];

        try {
            foreach ($items as $item) {
                if (isset($item[0], $item[1]) && is_numeric($item[0])) {
                    $element = null;
                    $shareRep = null;

                    if ($item[1] === 'Account') {
                        $element = $this->accountRepository->find($item[0]);
                        $shareRep = $this->accountShareRepository;
                    } elseif ($item[1] === 'Coupon') {
                        $element = $this->providerCouponRepository->find($item[0]);
                        $shareRep = $this->providerCouponShareRepository;
                    }

                    /** @var Account|Providercoupon $element */
                    if ($element && $this->isGranted('EDIT', $element)) {
                        $ids[] = $item[0];

                        foreach ($userAgents as $userAgent) {
                            $shareType = $shares[$userAgent->getUseragentid()];

                            if ($shareType == 0) {
                                $shareRep->removeShare($element, $userAgent);
                            } elseif ($shareType == 2) {
                                $shareRep->addShare($element, $userAgent);
                            }
                        }
                    }
                }
            }
        } catch (\LogicException $e) {
            $error = $e->getMessage();
        }

        $this->accountManager->storeLocalPasswords($this->getUser());

        if (isset($error)) {
            $response = [
                'success' => false,
                'error' => $error,
            ];
        } else {
            $accounts = $this->getAccounts(
                $ids,
                null,
                null,
                $this->resolveMapper(),
                null,
                true,
                true
            );
            $response = [
                'success' => true,
                'accounts' => $accounts['map'],
            ];
        }

        return (new JsonResponse($response))->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/backup-passwords",
     *      name="aw_account_backup_passwords",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function accountBackupPasswordsAction(
        Request $request,
        ReauthenticatorWrapper $reauthenticator,
        CsrfTokenManagerInterface $csrfTokenManager,
        AuthorizationCheckerInterface $authorizationChecker
    ) {
        // form post not have X-XSRF-TOKEN in headers
        if ($CSRF = $request->get('CSRF')) {
            $request->headers->set('X-XSRF-TOKEN', $CSRF);
        }

        if (!$authorizationChecker->isGranted('CSRF')) {
            throw new AccessDeniedHttpException('Invalid CSRF-token');
        }

        $action = Action::getBackupPasswordsAction();

        if (!$reauthenticator->isReauthenticated($action)) {
            return new JsonResponse(['success' => false]);
        }

        $this->accessControlList->setUser($this->tokenStorage->getBusinessUser());
        $ids = $request->request->get('accounts', []);

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        $passwords = [];
        $result = [];

        foreach ($ids as $fid) {
            if (!is_scalar($fid)) {
                continue;
            }

            if (!preg_match('/^a[0-9]+$/', $fid)) {
                $result[$fid] = false;

                continue;
            }
            $id = intval(preg_replace('/[^0-9]/', '', $fid));

            if (!$this->accessControlList->toAccount()->allow($id, 'edit')) {
                $result[$fid] = false;

                // $result[$fid] = $this->translator->trans(/** @Desc("No access to password") */ 'award.account.popup.backup.no-access');
                continue;
            }
            /** @var Account $account */
            $account = $this->accountRepository->find($id);

            if ($account->getSavepassword() == SAVE_PASSWORD_LOCALLY) {
                if ($this->localPasswordsManager->hasPassword($id)) {
                    $result[$fid] = true;
                    $passwords[$fid] = $account->getPass();
                } else {
                    $result[$fid] = $this->translator->trans(/** @Desc("Empty local password") */ 'award.account.popup.backup.empty-local-password');
                }
            } else {
                if ($account->getDatabasePass()) {
                    $result[$fid] = true;
                    $passwords[$fid] = $account->getPass();
                } else {
                    $result[$fid] = $this->translator->trans(/** @Desc("Empty database password") */ 'award.account.popup.backup.empty-database-password');
                }
            }
        }

        $download = $request->request->get('download', false);

        if ($download) {
            $reauthenticator->reset($action);
            $response = new Response($this->localPasswordsManager->encode($passwords), 200, [
                'Content-Type' => 'application/octet-stream',
                'content-disposition' => "attachment; filename=\"" . date('Y_m_d') . "_AWBackup.aw\"",
            ]);
        } else {
            $response = new JsonResponse([
                'success' => true,
                'accounts' => $result,
                'token' => $csrfTokenManager->getToken('')->getValue(),
            ]);
        }

        return $response;
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED')")
     * @Route("/restore-passwords",
     *      name="aw_account_restore_passwords",
     *      methods={"POST"},
     *      options={"expose"=true}
     * )
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function accountRestorePasswordsAction(Request $request, string $localPasswordsKey)
    {
        $this->accessControlList->setUser($this->tokenStorage->getBusinessUser());

        /* @var $file UploadedFile */
        $file = $request->files->get('passwords');

        if (empty($file) || !$file->isReadable()) {
            $response = [
                'success' => false,
                'error' => $this->translator->trans(/** @Desc("Empty file") */ 'award.account.popup.restore.empty-file'),
            ];

            return new Response("<script type='text/javascript'>window.parent.restoreActionCallback(" . json_encode($response) . ");</script>");
        }

        $passwords = [];
        $encoded = file_get_contents($file->getPathname());
        $err = 0;

        try {
            $passwords = $this->localPasswordsManager->decode($encoded, false);
        } catch (MalformedLocalPasswordsException $e) {
            $this->logger->log(Logger::WARNING, '[BackupRestore] ' . $e->getMessage());
            $filename = time() . '_' . $this->tokenStorage->getBusinessUser()->getUserid() . '_' . $file->getClientOriginalName();
            $file->move(self::FAILED_BACKUP_PATH, $filename);

            //			$mailer = $this->get('aw.email.mailer');
            //			$mailer->send(
            //				$mailer->getMessage()
            //					->setTo(ConfigValue(CONFIG_ERROR_EMAIL))
            //					->setSubject("Failed Restore Backup")
            //					->setBody("Error: " . $e->getMessage() . "<br/>" .
            //							  "UserID: " . $this->getBusinessUser()->getUserid() . "<br/>" .
            //							  "File: " . self::FAILED_BACKUP_PATH . '/' . $filename),
            //				['skip_stat' => true]
            //			);
            $err = 1;
        }

        // try old format
        if ($err) {
            $decoded = base64_decode($encoded, true);

            if (!empty($decoded)) {
                try {
                    $passwords = $this->localPasswordsManager->decode(base64_encode(AESEncode($decoded, $localPasswordsKey)), false);
                } catch (MalformedLocalPasswordsException $e) {
                    $err = 2;
                }
            } else {
                $err = 2;
            }
        }

        if ($err == 2) {
            $response = [
                'success' => false,
                'error' => $this->translator->trans(/** @Desc("Wrong file format") */ 'award.account.popup.restore.wrong-file'),
            ];

            return new Response("<script type='text/javascript'>window.parent.restoreActionCallback(" . json_encode($response) . ");</script>");
        }

        if (!count($passwords)) {
            $response = [
                'success' => false,
                'error' => $this->translator->trans(/** @Desc("Wrong file format") */ 'award.account.popup.restore.wrong-file'),
            ];

            return new Response("<script type='text/javascript'>window.parent.restoreActionCallback(" . json_encode($response) . ");</script>");
        }

        $ids = $request->request->get('accounts', []);

        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }

        $result = [];
        /** @var Account[] $changedAccounts */
        $changedAccounts = [];

        foreach ($ids as $fid) {
            if (!is_scalar($fid)) {
                continue;
            }
            $id = intval(preg_replace('/[^0-9]/', '', $fid));

            if (!$this->accessControlList->toAccount()->allow($id, 'edit')) {
                $result[$fid] = false;

                continue;
            }

            if (array_key_exists($id, $passwords) && !empty($passwords[$id])) {
                /** @var Account $account */
                $account = $this->accountRepository->find($id);
                $account->setPass($passwords[$id]);
                $result[$fid] = true;
                $changedAccounts[] = $account;
            } else {
                $result[$fid] = false;
            }
        }
        $this->entityManager->flush();

        foreach ($changedAccounts as $account) {
            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_RESTORE_PASSWORD));
            $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getId()), AccountChangedEvent::NAME);
        }

        $response = [
            'success' => true,
            'accounts' => $result,
        ];

        return new Response("<script type='text/javascript'>window.parent.restoreActionCallback(" . json_encode($response) . ");</script>");
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/set-goal", name="aw_account_json_setgoal", methods={"POST"}, options={"expose"=true})
     */
    public function setGoalAction()
    {
        $request = $this->requestStack->getCurrentRequest();
        $form = $this->createFormBuilder(null, [
            //			'cascade_validation' => true,
            'csrf_protection' => false,
        ])
            ->add('accounts', CollectionType::class, $this->getAccountsFormOptions())
            ->add('goal', NumberType::class, [
                'constraints' => [
                    new Assert\Regex(['pattern' => '/\d+/']),
                    new Assert\Range(['min' => 0, 'max' => 1000000000]),
                ],
            ])
            ->getForm();

        try {
            $form->submit($request->request->get($form->getName()));
        } catch (\Exception $e) {
            return new Response("Bad request", 400);
        }

        if (!($form->isSubmitted() && $form->isValid())) {
            return new Response("Bad request", 400);
        }

        $data = $form->getData();
        $data['goal'] = intval($data['goal']);
        $accounts = $this->getAccounts(
            $data['accounts'],
            null,
            ['ID', 'Access'],
            $this->resolveMapper(),
            function ($account) {
                return isset($account['Access']['edit']) && $account['Access']['edit'];
            },
            true
        );

        if (sizeof($accounts['ids']['accounts'])) {
            /** @var Connection $conn */
            $conn = $this->getDoctrine()->getConnection();

            if (empty($data['goal'])) {
                $conn->exec("UPDATE Account SET Goal = NULL, GoalAutoSet = 1, ModifyDate = NOW() WHERE AccountID IN (" . implode(",", $accounts['ids']) . ")");
            } else {
                $conn->exec("
                  UPDATE
                    Account
                  SET
                    GoalAutoSet = IF(" . $data['goal'] . " <> COALESCE(Goal, -1), 0, GoalAutoSet),
                    Goal = " . $data['goal'] . ",
                    ModifyDate = NOW()
                  WHERE AccountID IN (" . implode(",", $accounts['ids']['accounts']) . ")");
            }

            foreach ($accounts['ids']['accounts'] as $accountId) {
                /** @var Account $account */
                $account = $this->accountRepository->find($accountId);

                if ($account) {
                    $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_SET_GOAL));
                }
            }
        }

        // get new data
        $accounts = $this->getAccounts(
            $accounts['ids']['accounts'],
            null,
            null,
            $this->resolveMapper(),
            null,
            true
        );

        return (new JsonResponse([
            'success' => true,
            'accounts' => $accounts['map'],
        ]))->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * Переносит переданный в запросе аккаунт в архивные.
     *
     * @Route("/add-archive-account", name="aw_acount_json_addarchiveaccount", methods={"POST"}, options={"expose"=true})
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @throws AccessDeniedHttpException генерируется в случае, если в запросе был передан идентификатор аккаунта,
     * к которому у пользователя нет доступа
     */
    public function addArchiveAccountAction(Request $request): Response
    {
        $form = $this->createForm(ChangeAccountArchiveType::class);
        $form->handleRequest($request);

        if (!($form->isSubmitted() && $form->isValid())) {
            return new Response('Bad request', 400);
        }

        $data = $form->getData();

        foreach ($this->getAccountIds($data, 'accounts') as $id) {
            if (!$this->authorizationChecker->isGranted('EDIT', $this->accountRepository->find($id))) {
                throw new AccessDeniedHttpException('Access Denied');
            }
        }

        foreach ($this->getAccountIds($data, 'coupons') as $id) {
            if (!$this->authorizationChecker->isGranted('EDIT', $this->providerCouponRepository->find($id))) {
                throw new AccessDeniedHttpException('Access Denied');
            }
        }

        $accounts = $this->getAccounts(
            $this->getAccountIds($data, 'accounts'),
            $this->getAccountIds($data, 'coupons'),
            ['ID', 'Access'],
            $this->resolveMapper(),
            function ($account) {
                return isset($account['Access']['edit']) && $account['Access']['edit'];
            }
        );

        if (count($accounts['ids']['accounts']) > 0) {
            $this->accountRepository->updateIsArchivedValue($accounts['ids']['accounts'], $data['isArchived']);

            foreach ($accounts['ids']['accounts'] as $accountId) {
                /** @var Account $account */
                $account = $this->accountRepository->find($accountId);

                if ($account) {
                    $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_SET_ACTIVE));
                    $this->checkScheduler->schedule($account->getId());
                }
            }
        }

        if (count($accounts['ids']['coupons']) > 0) {
            $this->providerCouponRepository->updateIsArchivedValue($accounts['ids']['coupons'], $data['isArchived']);
        }

        $accounts = $this->getAccounts(
            $accounts['ids']['accounts'],
            $accounts['ids']['coupons'],
            null,
            $this->resolveMapper()
        );

        return (new JsonResponse([
            'success' => true,
            'accounts' => $accounts['map'],
        ]))->setEncodingOptions(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/remove-accounts", name="aw_account_json_remove", methods={"POST"}, options={"expose"=true})
     */
    public function removeAction()
    {
        $ids = json_decode($this->requestStack->getCurrentRequest()->getContent());
        $removed = [];

        foreach ($ids as $item) {
            if ($item->isCoupon) {
                /** @var Providercoupon $coupon */
                $coupon = $this->providerCouponRepository->find($item->id);

                if ($coupon === null) {
                    continue;
                }

                // If it's someone else's coupon, then just unshare it
                if (!$coupon->getOwner()->isFamilyMemberOfUser($this->tokenStorage->getBusinessUser())) {
                    if ($this->providerCouponShareRepository->removeShareWithUser($coupon, $this->tokenStorage->getBusinessUser())) {
                        $removed[] = 'c' . $item->id;
                    }
                } elseif ($this->isGranted("DELETE", $coupon)) {
                    $em = $this->getDoctrine()->getManager();
                    $em->remove($coupon);
                    $em->flush();
                    $removed[] = 'c' . $item->id;
                }
            } else {
                $account = $this->accountRepository->find($item->id);

                if ($account === null) {
                    continue;
                }

                if ($item->useragentid == 'my') {
                    if ($this->isGranted("DELETE", $account)) {
                        $this->accountRepository->deleteAccount($item->id);
                        $removed[] = 'a' . $item->id;
                    } else {
                        //						throw new AccessDeniedException();
                    }
                } else {
                    $ua = $this->useragentRepository->find($item->useragentid);

                    if (!empty($ua) && $ua->getClientid()) {
                        $this->accountShareRepository->removeSharedAccount($account, $ua);
                        $removed[] = 'a' . $item->id;
                    } else {
                        if ($this->isGranted("DELETE", $account)) {
                            $this->accountRepository->deleteAccount($item->id);
                            $removed[] = 'a' . $item->id;
                        } else {
                            //						throw new AccessDeniedException();
                        }
                    }
                }
            }
        }

        return new JsonResponse(['success' => true, 'removed' => $removed]);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/disable-enable-accounts",
     *     name="aw_account_json_enable_disable",
     *     methods={"POST"},
     *     defaults={"_format"="json"},
     *     options={"expose"=true}
     * )
     */
    public function enableDisableAction(Request $request)
    {
        $ids = $request->request->get('ids');
        $disabled = $request->request->get('disabled', false) == "true";

        $accountIds = array_map(function ($v) {
            return intval(preg_replace('/[^0-9]/', '', $v));
        }, $ids);
        /** @var Account[] $changedAccounts */
        $changedAccounts = [];

        foreach ($accountIds as $id) {
            $account = $this->accountRepository->find($id);

            // TODO Bad solution, maybe need use AccountSet and check once
            /** @var Account $account */
            if ($this->isGranted('EDIT', $account)) {
                $account->setDisabled($disabled);

                if ($disabled) {
                    $account->setDisableReason(Account::DISABLE_REASON_USER);
                    $account->setDisableDate(new \DateTime());
                } else {
                    $account->setDisableReason(null);
                    $account->setDisableDate(null);
                }

                $this->entityManager->persist($account);
                $changedAccounts[] = $account;
            }
        }
        $this->entityManager->flush();

        foreach ($changedAccounts as $account) {
            $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_DISABLE));
            $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getId()), AccountChangedEvent::NAME);
        }

        $accounts = $this->getAccounts(
            $ids,
            null,
            null,
            $this->resolveMapper(),
            null,
            true
        );

        return new JsonResponse([
            'accounts' => $accounts['map'],
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/disable-enable-background-updating-accounts",
     *     name="aw_account_json_enable_disable_background_updating",
     *     methods={"POST"},
     *     defaults={"_format"="json"},
     *     options={"expose"=true}
     * )
     */
    public function enableDisableBackgroundUpdatingAction(Request $request)
    {
        $ids = $request->request->get('ids');
        $disabled = $request->request->get('disabled', false) == "true";

        $accountIds = array_map(function ($v) {
            return intval(preg_replace('/[^0-9]/', '', $v));
        }, $ids);
        /** @var Account[] $changedAccounts */
        $changedAccounts = [];

        foreach ($accountIds as $id) {
            $account = $this->accountRepository->find($id);

            // TODO Bad solution, maybe need use AccountSet and check once
            /** @var Account $account */
            if ($this->isGranted('EDIT', $account)) {
                $account->setDisableBackgroundUpdating($disabled);
                $this->entityManager->persist($account);
                $changedAccounts[] = $account;
            }
        }
        $this->entityManager->flush();

        foreach ($changedAccounts as $account) {
            $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getId()), AccountChangedEvent::NAME);
        }

        $accounts = $this->getAccounts(
            $ids,
            null,
            null,
            $this->resolveMapper(),
            null,
            true
        );

        return new JsonResponse([
            'accounts' => $accounts['map'],
        ]);
    }

    /**
     * @Security("is_granted('ROLE_USER') and is_granted('NOT_USER_IMPERSONATED') and is_granted('CSRF')")
     * @Route("/confirm-changes",
     *     name="aw_account_json_confirm_changes",
     *     methods={"POST"},
     *     defaults={"_format"="json"},
     *     options={"expose"=true}
     * )
     */
    public function confirmChangesAction(Request $request)
    {
        $ids = $request->request->get('ids');

        $accountIds = array_map(function ($v) {
            return intval(preg_replace('/[^0-9]/', '', $v));
        }, $ids);

        foreach ($accountIds as $id) {
            $account = $this->accountRepository->find($id);

            /** @var Account $account */
            if ($this->isGranted('EDIT', $account)) {
                $account->setChangesConfirmed(true);
                $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_CONFIRM_CHANGES));
                $this->entityManager->persist($account);
            }
        }
        $this->entityManager->flush();

        $accounts = $this->getAccounts(
            $ids,
            null,
            null,
            $this->resolveMapper(),
            null,
            true
        );

        return new JsonResponse([
            'accounts' => $accounts['map'],
        ]);
    }

    /**
     * @param array $accountIds идентификаторы аккаунтов
     * @param array|null $couponIds идентификаторы купонов
     * @param array|null $fields
     * @param string $mapper
     * @param callable|null $filterCallback
     * @param bool $withCustomProgram
     * @param bool $withCoupons
     * @return array
     */
    private function getAccounts(
        $accountIds,
        $couponIds = null,
        $fields = null,
        $mapper = 'AwardWallet\\MainBundle\\Globals\\AccountList\\Mapper\\Mapper',
        $filterCallback = null,
        $withCustomProgram = false,
        $withCoupons = false
    ) {
        $accountIds = array_map(function ($v) {
            return intval(preg_replace('/[^0-9]/', '', $v));
        }, $accountIds);
        $accounts = [];
        $map = [];

        if ($accountIds) {
            $filter = " AND a.AccountID IN (" . implode(',', $accountIds) . ")";

            if (!$withCustomProgram) {
                $filter .= " AND a.ProviderID IS NOT NULL";
            }
            $couponFilter = (!$withCoupons) ? " AND 0 = 1" : " AND 1 = 1";
        } else {
            $filter = " AND 0 = 1";
        }

        if ($couponIds) {
            $couponFilter = " AND c.ProviderCouponID IN (" . implode(',', $couponIds) . ")";
        }

        if ($accountIds || $couponIds) {
            if (isset($fields) && !in_array('ID', $fields)) {
                $fields[] = 'ID';
            }
            $list = $this->accountListManager
                ->getAccountList(
                    $this->optionsFactory
                        ->createDefaultOptions()
                        ->set(Options::OPTION_USER, $this->tokenStorage->getBusinessUser())
                        ->set(Options::OPTION_FILTER, $filter)
                        ->set(Options::OPTION_COUPON_FILTER, $couponFilter)
                        ->set(Options::OPTION_FORMATTER, $this->mapper)
                )
                ->getAccounts();

            $accounts = ['accounts' => [], 'coupons' => []];

            foreach ($list as $account) {
                if (!isset($filterCallback) || $filterCallback($account)) {
                    $arrayName = ($account['TableName'] === 'Account') ? 'accounts' : 'coupons';
                    $accounts[$arrayName][] = (int) $account['ID'];
                    $map[] = $account;
                }
            }
        }

        return [
            'ids' => $accounts,
            'map' => $map,
        ];
    }

    /**
     * Получить массив идентификаторов аккаунтов без префиксов.
     *
     * @param array $data массив идентификаторов, пришедший в запросе
     * @param string|null $type тип записей: "accounts" или "coupons"
     */
    private function getAccountIds(array $data, ?string $type = null): array
    {
        $data['ids'] = ['accounts' => [], 'coupons' => []];

        foreach ($data['accounts'] as $id) {
            $arrayName = (substr($id, 0, 1) === 'a') ? 'accounts' : 'coupons';
            $data['ids'][$arrayName][] = (int) substr($id, 1);
        }

        return $type !== null ? $data['ids'][$type] : $data['ids'];
    }

    private function resolveMapper()
    {
        return 'AwardWallet\\MainBundle\\Globals\\AccountList\\Mapper\\DesktopListMapper';
    }

    private function getAccountsFormOptions()
    {
        return [
            'entry_type' => TextType::class,
            'delete_empty' => true,
            'allow_add' => true,
            'allow_delete' => true,
            'constraints' => [
                new Valid(),
                new Assert\NotBlank(),
                new Assert\Type(['type' => 'array']),
                new Assert\Count(['min' => 1, 'max' => 500]),
            ],
            'entry_options' => [
                'constraints' => [
                    new Assert\Regex(['pattern' => '/[ac]?\d+/']),
                ],
            ],
        ];
    }
}
