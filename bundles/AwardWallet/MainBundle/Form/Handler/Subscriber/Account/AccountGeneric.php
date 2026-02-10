<?php

namespace AwardWallet\MainBundle\Form\Handler\Subscriber\Account;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Subaccount;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Event\AccountChangedEvent;
use AwardWallet\MainBundle\Event\AccountFormSavedEvent;
use AwardWallet\MainBundle\Form\Account\Template;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Handler\GenericRequestHandler\HandlerEvent;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Transformer\AccountFormTransformer;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\FrameworkExtension\Exceptions\ImpersonatedException;
use AwardWallet\MainBundle\Globals\LoggerContext\ContextAwareLoggerWrapper;
use AwardWallet\MainBundle\Globals\StringHandler;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\AccountUpdateEvent;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Security\AntiBruteforceLockerService;
use AwardWallet\MainBundle\Security\RealUserDetector;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\Cache\CacheManager;
use AwardWallet\MainBundle\Service\Cache\Tags;
use AwardWallet\MainBundle\Service\EntitySerializer;
use AwardWallet\MainBundle\Service\PasswordLeakChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AccountGeneric implements EventSubscriberInterface
{
    public const LOCKER_KEY = "account_edit_2_";

    private AccountManager $accountManager;

    private AccountFormTransformer $dataTransformer;

    private EntitySerializer $serializer;

    private EntityManagerInterface $enityManager;

    private LoggerInterface $logger;

    private FormHandlerHelper $formHandlerHelper;

    private AuthorizationCheckerInterface $authorizationChecker;

    private AwTokenStorage $tokenStorage;

    private \Memcached $memcached;

    private RequestStack $requestStack;

    private int $ipLimit;

    private EventDispatcherInterface $eventDispatcher;

    private BalanceWatchManager $balanceWatchManager;

    private PasswordLeakChecker $leakChecker;

    private CacheManager $cacheManager;

    private RealUserDetector $realUserDetector;

    private BalanceProcessor $balanceProcessor;
    private LoggerInterface $loggerCtx;

    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorage $tokenStorage,
        AccountManager $accountManager,
        AccountFormTransformer $dataTransformer,
        FormHandlerHelper $formHandlerHelper,
        EntitySerializer $serializer,
        EntityManagerInterface $enityManager,
        LoggerInterface $logger,
        \Memcached $memcached,
        RequestStack $requestStack,
        $ipLimit,
        EventDispatcherInterface $eventDispatcher,
        BalanceWatchManager $balanceWatchManager,
        PasswordLeakChecker $leakChecker,
        CacheManager $cacheManager,
        RealUserDetector $realUserDetector,
        BalanceProcessor $balanceProcessor
    ) {
        $this->accountManager = $accountManager;
        $this->dataTransformer = $dataTransformer;
        $this->serializer = $serializer;
        $this->enityManager = $enityManager;
        $this->logger = $logger;
        $this->loggerCtx = (new ContextAwareLoggerWrapper($logger))
            ->withClass(self::class)
            ->withTypedContext();
        $this->formHandlerHelper = $formHandlerHelper;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->memcached = $memcached;
        $this->requestStack = $requestStack;
        $this->ipLimit = $ipLimit;
        $this->eventDispatcher = $eventDispatcher;
        $this->balanceWatchManager = $balanceWatchManager;
        $this->leakChecker = $leakChecker;
        $this->cacheManager = $cacheManager;
        $this->realUserDetector = $realUserDetector;
        $this->balanceProcessor = $balanceProcessor;
    }

    public static function getSubscribedEvents()
    {
        return [
            'form.generic.account.pre_handle' => ['preHandle'],
            'form.generic.account.on_valid' => ['onValid'],
        ];
    }

    public function preHandle(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var Account $account */
        $account = $form->getData();
        /** @var AccountModel $model */
        $model = $form->getNormData();
        $provider = $account->getProviderid();

        if (
            $provider
            && $account->getAccountid()
            && !$this->authorizationChecker->isGranted('SAVE', $account)
        ) {
            throw new AccessDeniedException();
        }

        if (null !== $account->getAccountid()) {
            return;
        }

        // new account

        $user = $this->tokenStorage->getBusinessUser();
        $account
            ->setUser($user)
            ->setSavepassword((($provider = $account->getProviderid()) && $provider->getPasswordrequired()) ?
                $user->getSavepassword() :
                SAVE_PASSWORD_DATABASE
            );

        if (!$provider) {
            $model
                ->setLogin('')
                ->setPass('');
        }
    }

    public function onValid(HandlerEvent $event)
    {
        $form = $event->getForm();
        /** @var AccountModel $model */
        $model = $form->getNormData();
        /** @var Account $account */
        $account = $model->getEntity();
        $provider = $account->getProviderid();
        $oldAccount = $model->getOldEntity();
        $connection = $this->tokenStorage->getBusinessUser()->getConnectionWith($form->getData()->getOwner()->getUser());

        if ($model->getLogin() === null) {
            $model->setLogin('');
        }

        if ($model->getPass() === null) {
            $model->setPass('');
        }

        /** @var Account $dirtyAccount */
        $dirtyAccount = $this->formHandlerHelper->copyProperties($model, clone $oldAccount, $this->dataTransformer->getProperties());

        if (null !== $connection && !$dirtyAccount->getOwner()->isBusiness()) {
            $dirtyAccount->addUserAgent($connection);
        }

        // safe credentialsChanged check on cloned account
        if (
            $dirtyAccount->credentialsChanged
            && $this->authorizationChecker->isGranted('USER_IMPERSONATED')
        ) {
            throw new ImpersonatedException();
        }

        if (!$this->authorizationChecker->isGranted('SAVE', $dirtyAccount)) {
            throw new AccessDeniedException();
        }

        // real save, real account entity is now "dirty"
        $this->formHandlerHelper->copyProperties($model, $account, $this->dataTransformer->getProperties());

        if (null !== $connection && !$account->getOwner()->isBusiness()) {
            $account->addUserAgent($connection);
        }

        // login constraint workaround for OAuth2 providers, eg. Capital One
        if (
            (
                $provider
                && (
                    $provider->isOauthProvider()
                    || StringUtils::isEmpty($provider->getLogincaption())
                )
            )
            && StringHandler::isEmpty($account->getLogin())
        ) {
            $account->setLogin('');
        }

        if ($account->credentialsChanged) {
            $account->setDisabled(false);
        }

        if ($account->isDisabled() && !$oldAccount->isDisabled()) {
            $account->setDisableReason(Account::DISABLE_REASON_USER);
            $account->setDisableDate(new \DateTime());
        }

        if (!$account->isDisabled()) {
            $account->setDisableReason(null);
            $account->setDisableDate(null);
        }

        $pass = $account->getPass();

        if (!empty($pass)) {
            $account->setPwnedTimes($this->leakChecker->checkPassword($pass));
        }

        // save new account for account storage operations
        if (!$account->getAccountid()) {
            $this->enityManager->persist($account);
            $this->enityManager->flush($account);
        }

        if ($account->getState() == ACCOUNT_PENDING) {
            $account->setState(ACCOUNT_ENABLED);
            $this->enityManager->persist($account);
            $this->enityManager->flush();

            $this->cacheManager->invalidateTags(
                Tags::getAllAccountsCounterTags($account->getUser()->getId()),
                false
            );
        }

        $this->accountManager->setAccountStorage($account, $oldAccount->getSavepassword(), $model->getSavePassword());

        if ($oldAccount->getBalance() !== $account->getBalance()) {
            $tester = static function (?float $test) {};

            try {
                $tester($account->getBalance());
            } catch (\Throwable $throwable) {
                $this->loggerCtx->info(
                    'Saving balance error: ' . $throwable->getMessage(),
                    [
                        'balance_value' => $account->getBalance(),
                    ],
                );
            }

            if ($this->balanceProcessor->saveAccountBalance($account, $account->getBalance(), true)) {
                $this->eventDispatcher->dispatch(
                    new AccountBalanceChangedEvent($account, [], AccountBalanceChangedEvent::SOURCE_MANUAL)
                );
            }
        }

        if (!empty($model->getExpirationDate())) {
            if (empty($oldAccount->getExpirationdate())) {
                $account->setExpirationautoset(EXPIRATION_USER);
            } else {
                if (
                    !empty($account->getExpirationdate())
                    && ($account->getExpirationdate()->format('Ymd') != $oldAccount->getExpirationdate()->format('Ymd'))
                    && ($account->getExpirationautoset() == EXPIRATION_UNKNOWN)
                ) {
                    $account->setExpirationautoset(EXPIRATION_USER);
                }
            }
        }

        if ($provider) {
            $this->callCheckerSaveForm($model->getTemplate(), $account, $form);

            if ($provider->isOauthProvider()) {
                $this->logger->info("saving capital one oauth, auth info length:" . strlen($account->getAuthInfo()), ["AccountID" => $account->getAccountid()]);
            }
        }

        if ($account->credentialsChanged) {
            $maxEditsPerUser = 40;

            $haveGoodScore = $this->realUserDetector->getScore($account->getUser()->getId())->getTotal() > 0.6;

            if ($haveGoodScore) {
                $maxEditsPerUser = 60;
            }

            if ($request = $this->requestStack->getCurrentRequest()) {
                $agent = $request->headers->get("User-Agent");

                if (stripos($agent, "HeadlessChrome") !== false) {
                    $maxEditsPerUser = 10;
                }
            }
            $this->checkLockout($account, $account->getUserid()->getUserid(), "user", $maxEditsPerUser);
            $this->checkLockout($account, $account->getId(), "account", 15);

            if ($account->getProviderid() !== null) {
                $this->checkLockout($account,
                    $account->getUserid()->getUserid() . '_' . $account->getProviderid()->getProviderid(),
                    "user_provider",
                    $haveGoodScore ? 30 : 15
                );
            }

            if ($this->ipLimit == 0 && $this->requestStack->getMasterRequest()->cookies->has('account_edits_per_hour_from_ip')) {
                $ipLimit = intval($this->requestStack->getMasterRequest()->cookies->get('account_edits_per_hour_from_ip'));

                if ($ipLimit < 0 || $ipLimit > 60) {
                    $ipLimit = 0;
                }
            } else {
                $ipLimit = $this->ipLimit;
            }

            if ($ipLimit > 0) {
                $this->checkLockout($account, $this->requestStack->getMasterRequest()->getClientIp(), "ip", $ipLimit);
            }
        }

        if ($form->has('BalanceWatch') && !empty($form->get('BalanceWatch')->getData()) && !$account->isBalanceWatchDisabled()) {
            $this->balanceWatchManager->startBalanceWatch($account, $model);
        }

        // hide subaccounts
        if ($form->has('hidesubaccount')) {
            $hideIds = array_map(function (Subaccount $subAccount) {
                return $subAccount->getId();
            }, $form->get('hidesubaccount')->getData()->toArray());

            /** @var Subaccount $subAccount */
            foreach ($account->getSubAccountsEntities() as $subAccount) {
                $subAccount->setIsHidden(in_array($subAccount->getId(), $hideIds));
            }
        }

        $this->eventDispatcher->dispatch(new AccountFormSavedEvent($account->getId()), AccountFormSavedEvent::NAME);
        $this->eventDispatcher->dispatch(new AccountChangedEvent($account->getId()), AccountChangedEvent::NAME);
        $this->eventDispatcher->dispatch(new AccountUpdateEvent($account, AccountUpdateEvent::SOURCE_ACCOUNT_FORM));
    }

    protected function callCheckerSaveForm(Template $template, Account $account, FormInterface $form)
    {
        $formData = $this->serializer->entityToArray($account);

        // add not mapped fields to data
        foreach ($form as $field) {
            if (!$field->getConfig()->getMapped()) {
                $formData[$field->getName()] = $field->getData();
            }
        }

        $template->checker->account = $account;
        $template->checker->SaveForm($formData);
    }

    private function checkLockout(Account $account, $key, $prefix, $maxAttempts)
    {
        $locker = new AntiBruteforceLockerService($this->memcached, self::LOCKER_KEY . $prefix, 60, 60, $maxAttempts, "Too many edits per hour", $this->logger);

        if (!empty($locker->checkForLockout((string) $key))) {
            if (!$account->getUserid()->isFraud()) {
                $this->logger->critical("marking user as fraud, because too many edits\creations of account", ["AccountID" => $account->getAccountid(), "UserID" => $account->getUserid()->getUserid(), "Prefix" => $prefix, "Key" => $key]);
                $account->getUserid()->setFraud(true);
                $this->enityManager->persist($account->getUserid());
                $this->enityManager->flush();
            } else {
                $this->logger->warning("too many edits\creations of accounts, already marked as fraud", ["AccountID" => $account->getAccountid(), "UserID" => $account->getUserid()->getUserid(), "Prefix" => $prefix, "Key" => $key]);
            }
        }
    }
}
