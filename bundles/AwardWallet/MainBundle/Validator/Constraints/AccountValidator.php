<?php

namespace AwardWallet\MainBundle\Validator\Constraints;

use AwardWallet\MainBundle\Entity\Account as AccountEntity;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Form\Handler\FormHandlerHelper;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\Form\Transformer\AccountFormTransformer;
use AwardWallet\MainBundle\Form\Transformer\DocumentTransformer;
use AwardWallet\MainBundle\Form\Transformer\ProviderCouponFormTransformer;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\FrameworkExtension\Translator\TranslatorHijacker;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Manager\AccountManager;
use AwardWallet\MainBundle\Manager\LocalPasswordsManager;
use AwardWallet\MainBundle\Service\AccountCounter\Counter as AccountCounter;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Validator\Constraints\Cause\ExistingAccount;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

use function AwardWallet\MainBundle\Globals\Utils\iterFluent\it;

class AccountValidator extends ConstraintValidator
{
    private $em;
    private $accountRepo;
    private $couponRepo;
    private $usrRepo;
    private $propertyAccessor;
    private $translator;
    private $router;
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;
    /**
     * @var AwTokenStorageInterface
     */
    private $tokenStorage;
    /**
     * @var AccountFormTransformer
     */
    private $accountTransformer;
    /**
     * @var ProviderCouponFormTransformer
     */
    private $couponTransformer;
    /**
     * @var LocalPasswordsManager
     */
    private $localPasswordsManager;
    /**
     * @var AccountManager
     */
    private $accountManager;
    /**
     * @var FormHandlerHelper
     */
    private $formHandlerHelper;
    /**
     * @var DocumentTransformer
     */
    private $documentTransformer;

    private GlobalVariables $globalVariables;

    private AccountCounter $accountCounter;

    public function __construct(
        EntityManagerInterface $em,
        PropertyAccessorInterface $propertyAccessor,
        FormHandlerHelper $formHandlerHelper,
        TranslatorHijacker $translator,
        RouterInterface $router,
        AwTokenStorageInterface $tokenStorage,
        AuthorizationCheckerInterface $authorizationChecker,
        AccountFormTransformer $accountTransformer,
        ProviderCouponFormTransformer $couponTransformer,
        DocumentTransformer $documentTransformer,
        LocalPasswordsManager $localPasswordsManager,
        AccountManager $accountManager,
        GlobalVariables $globalVariables,
        AccountCounter $accountCounter
    ) {
        $this->em = $em;
        $this->accountRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);
        $this->couponRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Coupon::class);
        $this->usrRepo = $em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class);
        $this->propertyAccessor = $propertyAccessor;
        $this->translator = $translator;
        $this->router = $router;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->accountTransformer = $accountTransformer;
        $this->couponTransformer = $couponTransformer;
        $this->localPasswordsManager = $localPasswordsManager;
        $this->accountManager = $accountManager;
        $this->formHandlerHelper = $formHandlerHelper;
        $this->documentTransformer = $documentTransformer;
        $this->globalVariables = $globalVariables;
        $this->accountCounter = $accountCounter;
    }

    /**
     * @param AccountModel $accountModel
     */
    public function validate($accountModel, Constraint $constraint)
    {
        global $eliteUsers;

        /** @var AccountEntity $dirtyAccount */
        $dirtyAccount = clone (method_exists($accountModel, 'getEntity') ? $accountModel->getEntity() : $accountModel);
        $this->applyData($dirtyAccount, $accountModel);
        $connection = $this->tokenStorage->getBusinessUser()->getConnectionWith($accountModel->getOwner()->getUser());

        if (null !== $connection && !$dirtyAccount->getOwner()->isBusiness()) {
            $dirtyAccount->addUserAgent($connection);
        }

        /** @var Usr $currentUser */
        $currentUser = $this->tokenStorage->getBusinessUser();
        $isEliteMember = in_array($currentUser->getUserid(), $eliteUsers);
        $isBusinessMode = $this->authorizationChecker->isGranted('SITE_BUSINESS_AREA');
        $userId = $dirtyAccount->getUserid()->getUserid();
        $useragentId = $dirtyAccount->getUseragentid() ? $dirtyAccount->getUseragentid()->getUseragentid() : 0;

        if ($dirtyAccount instanceof AccountEntity) {
            $this->validateSharing($dirtyAccount, $this->context);

            $providerId = $dirtyAccount->getProviderid() ? $dirtyAccount->getProviderid()->getProviderid() : null;
            $isCustom = !isset($providerId);
            $addMode = empty($dirtyAccount->getAccountid());
            $criteria = [];

            foreach (['user', 'providerid', 'programname', 'login', 'login2', 'login3'] as $field) {
                $value = $this->propertyAccessor->getValue($dirtyAccount, $field);

                if (!empty($value)) {
                    if ($value instanceof \DateTimeInterface) {
                        $value = $value->format('Y-m-d');
                    }
                    $criteria[$field] = $value;
                }
            }
            $found =
                (
                    $providerId
                    && (
                        $dirtyAccount->getProviderid()->isOauthProvider()
                        || StringUtils::isEmpty($dirtyAccount->getProviderid()->getLogincaption())
                    )
                ) ?
                    [] :
                    $this->accountRepo->findBy($criteria);

            $found = array_filter($found, function (AccountEntity $existing) {
                return !in_array($existing->getState(), [ACCOUNT_PENDING, ACCOUNT_IGNORED]);
            });

            if (!empty($dirtyAccount->getAccountid())) {
                $found = array_filter($found, function (AccountEntity $existing) use ($dirtyAccount) {
                    return $existing->getAccountid() != $dirtyAccount->getAccountid();
                });
            }

            if (!empty($found)) {
                $existing = array_shift($found);
            }
            /** @var ExecutionContextInterface $context */
            $context = $this->context;

            if (!empty($existing)) {
                [$message, $cause] = $this->getExistingAccountViolationData($dirtyAccount, $existing);
                $context
                    ->buildViolation($message)
                    ->setCause($cause)
                    ->addViolation();
            }

            if (
                !($providerId && $dirtyAccount->getProviderid()->isOauthProvider())
                && $dirtyAccount->getAccountid()
                && ($accountModel->getSavePassword() === SAVE_PASSWORD_LOCALLY)
                && (null === $accountModel->getPass() && null !== $providerId)
                && (!$this->localPasswordsManager->hasPassword($dirtyAccount->getAccountid()))
            ) {
                $context
                    ->buildViolation($this->translator->trans('error.award.account.missing-password.text'))
                    ->atPath($context->getPropertyPath('pass'))
                    ->addViolation();
            }

            $this->checkInvalidSymbols($accountModel, $context);

            if ($accountModel->getBalanceWatch() && !$dirtyAccount->isBalanceWatchDisabled()) {
                $this->validateBalanceWatch($accountModel, $context);
            }

            if (SAVE_PASSWORD_LOCALLY === $accountModel->getSavePassword() && null !== $accountModel->getBalanceWatchStartDate()) {
                $context->buildViolation($this->translator->trans('account.balancewatch.not-available-password-local'))->atPath($context->getPropertyPath('pass'))->addViolation();
            }
        } else {
            $addMode = empty($dirtyAccount->getProvidercouponid());
        }

        $accountSummary = $this->accountCounter->calculate($currentUser->getId());
        $accounts = $accountSummary->getCount();

        if (!$isBusinessMode && $addMode && $accounts >= PERSONAL_INTERFACE_MAX_ACCOUNTS && !$isEliteMember) {
            $this->context->buildViolation($this->getPersonalInterfaceError($currentUser, $accounts))->addViolation();

            return;
        }

        $pendingAccount = false;

        if (!$addMode) {
            if ($dirtyAccount instanceof AccountEntity) {
                $id = $dirtyAccount->getAccountid();
                $sql = "SELECT UserID, UserAgentID, State FROM Account WHERE AccountID = ?";
            } else {
                $id = $dirtyAccount->getProvidercouponid();
                $sql = "SELECT UserID, UserAgentID FROM ProviderCoupon WHERE ProviderCouponID = ?";
            }
            $stmt = $this->em->getConnection()->executeQuery($sql, [$id], [\PDO::PARAM_INT]);
            $oldFields = $stmt->fetchAssociative();
            $pendingAccount = $dirtyAccount instanceof AccountEntity && is_array($oldFields) && $oldFields['State'] == ACCOUNT_PENDING;
        }

        if (
            !$addMode
            && $oldFields
            && $oldFields['UserID'] == $userId
            && $oldFields['UserAgentID'] == $useragentId
            && !$pendingAccount
        ) {
            $skip = true;
        }

        if (!isset($skip)) {
            if ($dirtyAccount instanceof AccountEntity) {
                if (!$addMode) {
                    $displayName = $dirtyAccount->getDisplayname();
                } else {
                    if ($isCustom) {
                        $displayName = $dirtyAccount->getProgramname();
                    } else {
                        $displayName = $dirtyAccount->getProviderid()->getDisplayname();
                    }
                }
            } else {
                $displayName = $dirtyAccount->getProgramname();
            }

            $userAccountSummary = $this->accountCounter->calculate($userId);

            if (
                ($userId != $currentUser->getId() || $pendingAccount)
                && !$dirtyAccount->getUser()->isBusiness()
                && !in_array($userId, $eliteUsers)
            ) {
                $countAccounts = $userAccountSummary->getCount();

                if ($countAccounts >= PERSONAL_INTERFACE_MAX_ACCOUNTS) {
                    if ($userId != $currentUser->getId()) {
                        $msg = $this->translator->trans(
                            /** @Desc("You are about to change the owner of %displayName% account to the new name: '%name%', however this account already has %count% accounts added to it. Such transaction would put this account over the %limit% account limit. Under the current personal interface you should not have more than %limit% loyalty accounts added.") */
                            "account.notice.account.personal-max-accounts.change-owner",
                            [
                                '%displayName%' => $displayName,
                                '%name%' => $dirtyAccount->getUser()->getFullName(),
                                '%count%' => $countAccounts,
                                '%limit%' => PERSONAL_INTERFACE_MAX_ACCOUNTS,
                            ]
                        );
                    } else {
                        $msg = $this->translator->trans(
                            /** @Desc("AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use. There is another business interface that we have: <a href='http://business.AwardWallet.com' target='_blank'>http://business.AwardWallet.com</a>. Your account looks like a business account based on the fact that you have added a total of %count% loyalty programs.") */
                            'account.notice.account.personal-max-accounts',
                            [
                                '%count%' => $countAccounts,
                            ]
                        );
                    }
                    $this->context->buildViolation($msg)->addViolation();

                    return;
                }
            }

            $countAccounts = $userAccountSummary->getCount($useragentId);

            if ($countAccounts >= MAX_ACCOUNTS_PER_PERSON && !in_array($userId, $eliteUsers)) {
                if ($userId == $currentUser->getId()) {
                    $this->context->buildViolation(
                        $this->translator->trans(
                            /** @Desc("You are not allowed to have more than %max% loyalty accounts per person, <a href='%url%'>please organize your users</a> and assign loyalty programs to their corresponding owners.") */
                            "account.notice.account.personal-max-per-person",
                            [
                                '%max%' => MAX_ACCOUNTS_PER_PERSON,
                                '%url%' => $isBusinessMode ? $this->router->generate('aw_business_members') : $this->router->generate('aw_user_connections'),
                            ]
                        )
                    )->addViolation();

                    return;
                } else {
                    $this->context->buildViolation(
                        $this->translator->trans(
                            /** @Desc("You are about to change the owner of %displayName% account to the new name: '%name%', however this account already has %count% accounts added to it. Such transaction would put this account over the %limit% account limit. Under the current interface you should not have more than %limit% loyalty accounts per person.") */
                            "account.notice.account.personal-max-per-person.change-owner",
                            [
                                '%displayName%' => $displayName,
                                '%name%' => $dirtyAccount->getUser()->getFullName(),
                                '%count%' => $countAccounts,
                                '%limit%' => MAX_ACCOUNTS_PER_PERSON,
                            ]
                        )
                    )->addViolation();

                    return;
                }
            }

            // TODO provider 165 is hardcoded. Case https://redmine.awardwallet.com/issues/14728
            if ($dirtyAccount instanceof AccountEntity && !$isCustom && $providerId != 165) {
                $countAccounts = $userAccountSummary->getCountAccountsByProviderIds([$providerId], $useragentId);

                if ($countAccounts >= MAX_LIKE_LP_PER_PERSON && !in_array($userId, $eliteUsers)) {
                    /** @Desc("You can't have more than %MAX_LIKE_LP_PER_PERSON% accounts for the same provider listed under the same person, please choose another person as the owner of this loyalty program") */
                    $message = $this->translator->trans(
                        'account.notice.account.max_like_lp',
                        [
                            '%MAX_LIKE_LP_PER_PERSON%' => MAX_LIKE_LP_PER_PERSON,
                        ]
                    );
                    $this->context->buildViolation($message)->addViolation();
                }
            }
        }
    }

    public function validateBalanceWatch(AccountModel $accountModel, ExecutionContextInterface $context): ?bool
    {
        if (null !== $accountModel->getBalanceWatchStartDate()) {
            return null;
        }

        $user = $this->tokenStorage->getBusinessUser();

        if (empty($accountModel->getEntity()->getProviderid()->getCancheck())
            || false === $accountModel->getEntity()->getProviderid()->getCancheckbalance()
            || !in_array($accountModel->getEntity()->getProviderid()->getState(), BalanceWatchManager::ALLOW_PROVIDER_STATE)) {
            $context->buildViolation($this->translator->trans('account.balancewatch.not-available-not-cancheck'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        }

        if (!$user->isBusiness()) {
            $isOwner = $accountModel->getUserid()->getUserid() === $this->tokenStorage->getUser()->getUserid();

            if (($isOwner && !$accountModel->getUserid()->isAwPlus() && 0 >= $accountModel->getUserid()->getBalanceWatchCredits())
                || (!$isOwner && !$this->tokenStorage->getUser()->isAwPlus() && 0 >= $this->tokenStorage->getUser()->getBalanceWatchCredits())) {
                $context->buildViolation($this->translator->trans('account.balancewatch.awplus-upgrade'))
                    ->atPath('BalanceWatch')
                    ->addViolation();

                return false;
            }
        }

        if (!$user->isBusiness() && 0 === $this->tokenStorage->getUser()->getBalanceWatchCredits()) {
            $context->buildViolation($this->translator->trans('account.balancewatch.credits-no-available-notice'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        } elseif ($user->isBusiness() && $user->getBusinessInfo()->getBalance() < BalanceWatchCredit::PRICE) {
            $context->buildViolation($this->translator->trans('account.balancewatch.credits-no-available-notice-business'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        }

        if ($accountModel->isDisabled() /* || $accountModel->getEntity()->isDisabled() */) {
            $context->buildViolation($this->translator->trans('account.balancewatch.not-available-account-disabled'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        }

        if (ACCOUNT_CHECKED !== $accountModel->getEntity()->getErrorcode()) {
            $context->buildViolation($this->translator->trans('account.balancewatch.not-available-account-error'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        }

        if (SAVE_PASSWORD_LOCALLY == $accountModel->getSavePassword() && !in_array($accountModel->getEntity()->getProviderid()->getProviderid(), BalanceWatchManager::EXCLUDED_PROVIDER_LOCAL_PASSWORD) /* || SAVE_PASSWORD_LOCALLY == $accountModel->getEntity()->getSavepassword() */) {
            $context->buildViolation($this->translator->trans('account.balancewatch.not-available-password-local'))
                ->atPath('BalanceWatch')
                ->addViolation();

            return false;
        }

        if (!in_array($accountModel->getPointsSource(), array_keys(BalanceWatch::POINTS_SOURCES))) {
            $context->buildViolation($this->translator->trans('pattern', [], 'validators'))
                ->atPath('PointsSource')
                ->addViolation();

            return false;
        }

        if (!empty($accountModel->getSourceProgramRegion())) {
            $tranfserFromProvider = $accountModel->getTransferFromProvider();

            if (empty($tranfserFromProvider)) {
                $accountModel->setSourceProgramRegion(null);
            } else {
                $login2Options = $this->accountManager->fetchLogin2Options(
                    $tranfserFromProvider,
                    $accountModel->getUserid()
                ) ?? null;

                if (empty($login2Options)
                    || !is_array($login2Options)
                    || !array_key_exists($accountModel->getSourceProgramRegion(), $login2Options)) {
                    $context->buildViolation($this->translator->trans('pattern', [], 'validators'))
                        ->atPath('ProviderRegion')
                        ->addViolation();
                }
            }
        }

        if (!empty($accountModel->getExpectedPoints()) && (0 > $accountModel->getExpectedPoints())) {
            $context->buildViolation($this->translator->trans('pattern', [], 'validators'))
                ->atPath('ExpectedPoints')
                ->addViolation();

            return false;
        }

        return true;
    }

    private function applyData($entity, $model)
    {
        $transformer = $entity instanceof AccountEntity ?
            $this->accountTransformer :
            (
                (
                    ($entity->getKind() == PROVIDER_KIND_DOCUMENT)
                    && \in_array($entity->getTypeid(), array_keys(Providercoupon::DOCUMENT_TYPES))
                ) ?
                    $this->documentTransformer :
                    $this->couponTransformer
            );

        $this->formHandlerHelper->copyProperties($model, $entity, $transformer->getProperties());
    }

    private function getPersonalInterfaceError(Usr $currentUser, $count)
    {
        $result = $this->translator->trans(
            /** @Desc("AwardWallet.com is a personal interface for managing loyalty programs and is not intended for business use. There is another business interface that we have: <a href='http://business.AwardWallet.com' target='_blank'>http://business.AwardWallet.com</a>. Your account looks like a business account based on the fact that you have added a total of %count% loyalty programs.") */
            'account.notice.account.personal-max-accounts',
            [
                '%count%' => $count,
            ]
        );

        if (!$this->usrRepo->getBusinessByUser($currentUser)) {
            $result .= $this->translator->trans(
                /** @Desc("Please <a href='%link%'>convert this personal account to a business account</a>.") */
                'account.notice.account.personal-max-accounts.not-business',
                [
                    '%link%' => $this->router->generate('aw_user_convert_to_business'),
                ]
            );
        } else {
            $result .= $this->translator->trans(
                /** @Desc("Please <a href='%link%'>login to your business account</a>.") */
                'account.notice.account.personal-max-accounts.business',
                [
                    '%link%' => 'http://business.' . $_SERVER['HTTP_HOST'] . $this->router->generate('aw_home') . "#login",
                ]
            );
        }

        return $result;
    }

    /**
     * @return [string, CauseAwareInterface]
     */
    private function getExistingAccountViolationData(AccountEntity $account, AccountEntity $existing)
    {
        $cause = new ExistingAccount($existing);
        $message = $this->translator->trans(
            'account.notice.account.already.added',
            [
                '%DisplayName%' => $account->getDisplayName(),
                '%Login%' => $existing->getProviderid() ? $existing->getLogin() : $existing->getProgramname(),
                '%Name%' => $existing->getOwnerFullName(),
            ]
        );

        if ($this->authorizationChecker->isGranted('EDIT', $existing)) {
            $message .= $this->translator->trans(
                'account.notice.edit.existing',
                [
                    '%EditLink%' => $this->router->generate('aw_account_edit', ['accountId' => $existing->getAccountid()]),
                ]
            );
        } else {
            $message .= '(2) ' . $this->translator->trans(
                'account.error.sharing.required',
                ['%Name%' => $existing->getOwnerFullName()]
            );
        }

        return [$message, $cause];
    }

    private function checkInvalidSymbols(AccountModel $accountModel, ExecutionContextInterface $context): void
    {
        for ($ascii = '', $i = 1; $i < 32; $i++) {
            $ascii .= \chr($i);
        }
        $uChars = ['⓪', '⓪', '①', '②', '③', '④', '⑤', '⑥', '⑦', '⑧', '⑨', '⑩', '⑪', '⑫', '⑬', '⑭', '⑮', '⑯', '⑰', '⑱', '⑲', '⑳', '➀', '➁', '➂', '➃', '➄', '➅', '➆', '➇', '➈', '➉', '⓿', '❶', '❷', '❸', '❹', '❺', '❻', '❼', '❽', '❾', '❿', '➊', '➋', '➌', '➍', '➎', '➏', '➐', '➑', '➒', '➓', '⓫', '⓬', '⓭', '⓮', '⓯', '⓰', '⓱', '⓲', '⓳', '⓴'];
        $entityNum = array_merge(\range(8192, 8207), \range(8234, 8239), \range(8287, 8303));

        foreach (['Login', 'Login2', 'Login3', 'Pass'] as $field) {
            if (method_exists($accountModel, 'get' . $field) && !empty($accountModel->{'get' . $field}())) {
                $origin = $accountModel->{'get' . $field}();

                if ($origin instanceof \DateTimeInterface) {
                    continue;
                }
                $origin = trim($origin, $ascii);

                for ($i = 0, $iCount = \mb_strlen($origin); $i < $iCount; $i++) {
                    $sym = mb_substr($origin, $i, 1);

                    if (false !== mb_strpos($ascii, $sym)
                        || /* 'Pass' !== $field && */ in_array($sym, $uChars)
                    ) {
                        $context
                            ->buildViolation($this->translator->trans('invalid.symbols', [], 'validators'))
                            ->atPath(strtolower($field))
                            ->addViolation();

                        break;
                    }
                }

                $cleaned = preg_replace_callback("/([\340-\357])([\200-\277])([\200-\277])/", function ($matches) use ($entityNum) {
                    $code = (\ord($matches[1]) - 224) * 4096 + (\ord($matches[2]) - 128) * 64 + (\ord($matches[3]) - 128);

                    if (\in_array($code, $entityNum, true)) {
                        return '';
                    }

                    return $matches[0];
                }, $origin);
                $bom = mb_convert_encoding("&#65279;", "unicode", "HTML-ENTITIES");
                $cleaned = preg_replace("/$bom/", '', $cleaned);

                if (mb_strlen($origin) !== mb_strlen($cleaned)) {
                    $context
                        ->buildViolation($this->translator->trans('invalid.symbols', [], 'validators'))
                        ->atPath(strtolower($field))
                        ->addViolation();

                    break;
                }

                /** masked variants
                 * ****1234
                 * ****4321
                 * 12**34.
                 */
                if ('Login' === $field && $accountModel->getEntity()->getState() === ACCOUNT_PENDING) {
                    if (preg_match('/^[*]{4}[^*]+$|^[^*]+[*]{4}$|^[^*]+[*]{2}[^*]+$/', $origin) > 0) {
                        $context
                            ->buildViolation($this->translator->trans('invalid.symbols', [], 'validators'))
                            ->atPath(strtolower($field))
                            ->addViolation();
                    }
                }
            }
        }
    }

    private function validateSharing(AccountEntity $account, ExecutionContextInterface $context)
    {
        $user = $account->getUser();
        $userAgents = $account->getUserAgents();
        $hasInvalidSharing = it($userAgents)->any(fn (Useragent $ua) => $ua->getAgentid()->getId() == $user->getId());

        if ($hasInvalidSharing) {
            $context
                ->buildViolation($this->translator->trans(
                    /** @Desc("You can't share an account with a user who already owns it") */ 'invalid_sharing', [], 'validators'
                ))
                ->addViolation();
        }
    }
}
