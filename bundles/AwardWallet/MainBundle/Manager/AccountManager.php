<?php

namespace AwardWallet\MainBundle\Manager;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Owner;
use AwardWallet\MainBundle\Entity\Provider;
use AwardWallet\MainBundle\Entity\Providercoupon;
use AwardWallet\MainBundle\Entity\Repositories\UseragentRepository;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorage;
use AwardWallet\MainBundle\Globals\GlobalVariables;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class AccountManager
{
    protected LocalPasswordsManager $localPassManager;

    private Connection $connection;

    private EntityManagerInterface $em;

    private AuthorizationCheckerInterface $authorizationChecker;

    private AwTokenStorage $tokenStorage;

    private LoggerInterface $logger;

    private GlobalVariables $globalVariables;

    public function __construct(
        LocalPasswordsManager $localPassManager,
        Connection $connection,
        EntityManagerInterface $em,
        AuthorizationCheckerInterface $authorizationChecker,
        AwTokenStorage $tokenStorage,
        LoggerInterface $logger,
        GlobalVariables $globalVariables
    ) {
        $this->localPassManager = $localPassManager;
        $this->connection = $connection;
        $this->em = $em;
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->logger = $logger;
        $this->globalVariables = $globalVariables;
    }

    public function setAccountStorage(Account $account, $oldStorage, $newStorage)
    {
        $localPasswordsManager = $this->localPassManager;
        $accountId = $account->getAccountid();

        switch ($oldStorage) {
            case SAVE_PASSWORD_LOCALLY:
                if ($localPasswordsManager->hasPassword($accountId)) {
                    $oldPassword = $localPasswordsManager->getPassword($accountId);
                } else {
                    $oldPassword = "";
                }

                switch ($newStorage) {
                    case SAVE_PASSWORD_LOCALLY:
                        if ($account->getPass() != $oldPassword) {
                            $localPasswordsManager->setPassword($accountId, $account->getPass());
                        }

                        break;

                    case SAVE_PASSWORD_DATABASE:
                        if (empty($account->getDatabasePass()) && !empty($oldPassword)) {
                            $account->setPass($oldPassword);
                            $account->setSavepassword($newStorage);
                            $localPasswordsManager->removePassword($accountId);
                            $this->em->flush();
                        } elseif (!empty($account->getDatabasePass())) {
                            $account->setSavepassword($newStorage);
                            $localPasswordsManager->removePassword($accountId);
                        }

                        break;
                }

                break;

            case SAVE_PASSWORD_DATABASE:
                switch ($newStorage) {
                    case SAVE_PASSWORD_LOCALLY:
                        $pass = $account->getPass();

                        if (!empty($pass)) {
                            $localPasswordsManager->setPassword($accountId, $pass);
                            $account->setSavepassword($newStorage);
                        }

                        break;

                    case SAVE_PASSWORD_DATABASE:
                        break;
                }

                break;
        }
    }

    /**
     * @param Account|Providercoupon $account
     */
    public function setOwner($account, Usr $user, ?Useragent $ua = null)
    {
        if (!$this->authorizationChecker->isGranted('EDIT', $account)) {
            throw new \Exception("No write access to account " . $account->getAccountid() . " while changing owner, NewUserID: {$user->getUserid()}, NewAgentID: " . (empty($ua) ? null : $ua->getUseragentid()));
        }

        if (empty($ua)) {
            $newOwner = $user;
        } else {
            if ($ua->isFamilyMember()) {
                $newOwner = $ua->getAgentid();
            } else {
                $newOwner = $ua->getClientid();
            }
        }
        $userChanged = $account->getUserid()->getUserid() != $newOwner->getUserid();
        $me = $this->tokenStorage->getBusinessUser();
        $connection = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)->findOneBy(["agentid" => $me, "clientid" => $newOwner]);

        if ($newOwner->getUserid() != $me->getUserid() && !$newOwner->isBusiness()) {
            if (empty($connection) || !$this->authorizationChecker->isGranted('EDIT_ACCOUNTS', $connection)) {
                throw new \Exception("There are no approved write connection between {$newOwner->getUserid()} and {$me->getUserid()}");
            }
        }
        $account->setUserid($newOwner);
        $account->setUseragentid(!empty($ua) && $ua->isFamilyMember() ? $ua : null);

        if ($userChanged) {
            if ($newOwner->isBusiness()) {
                // SCvetkov says it is requirement - no share to personal, when moving account to business
                $account->setUseragents([]);
            } else {
                /** @var Useragent $connection */
                if (!empty($connection)) {
                    $account->setUseragents([$connection]);
                }
            }
        }
    }

    public function changeAccountsOwner(Owner $oldOwner, Owner $newOwner)
    {
        $accountRepository = $this->em->getRepository(Account::class);
        /** @var Account[] $accounts */
        $accounts = $accountRepository->findBy(['user' => $oldOwner->getUser(), 'userAgent' => $oldOwner->getFamilyMember()]);

        foreach ($accounts as $account) {
            $this->logger->info('changing account owner', [
                'AccountID' => $account->getAccountid(),
                'OldUserID' => $account->getUserid(),
                'OldUserAgentID' => $account->getUseragentid() ? $account->getUseragentid()->getUseragentid() : null,
                'NewUserID' => $newOwner->getUser()->getUserid(),
                'NewUserAgentID' => $newOwner->getFamilyMember() ? $newOwner->getFamilyMember()->getUseragentid() : null,
            ]);
            $account->setUserid($newOwner->getUser());
            $account->setUseragentid($newOwner->getFamilyMember());
            $this->em->persist($account);
        }

        $providerCouponRep = $this->em->getRepository(Providercoupon::class);
        /** @var Providercoupon[] $coupons */
        $coupons = $providerCouponRep->findBy(['user' => $oldOwner->getUser(), 'userAgent' => $oldOwner->getFamilyMember()]);

        foreach ($coupons as $coupon) {
            $this->logger->info('changing providercoupon owner', [
                'ProviderCouponID' => $coupon->getProvidercouponid(),
                'OldUserID' => $coupon->getUserid(),
                'OldUserAgentID' => $coupon->getUseragentid() ? $coupon->getUseragentid()->getUseragentid() : null,
                'NewUserID' => $newOwner->getUser()->getUserid(),
                'NewUserAgentID' => $newOwner->getFamilyMember() ? $newOwner->getFamilyMember()->getUseragentid() : null,
            ]);
            $coupon->setUser($newOwner->getUser())
                   ->setUseragent($newOwner->getFamilyMember());
            $this->em->persist($coupon);
        }
    }

    /**
     * @param string $question
     * @param string $answer
     * @return bool
     */
    public function answerSecurityQuestion(Account $account, $question, $answer)
    {
        if (!$this->authorizationChecker->isGranted('UPDATE', $account)) {
            return false;
        }

        $this->connection->executeUpdate(
            "INSERT INTO Answer(AccountID, Question, Answer, Valid, CreateDate)
                VALUES(:account, :question, :answer, 1, now())
                ON DUPLICATE KEY UPDATE Answer = :answer2, Valid = 1, CreateDate = now()",
            [
                ':account' => intval($account->getAccountId()),
                ':question' => $question,
                ':answer' => $answer,
                ':answer2' => $answer,
            ]
        );

        return true;
    }

    public function storeLocalPasswords(Usr $user)
    {
        $accounts = $this->connection->executeQuery(
            "SELECT a.AccountID as ID, ua.AccessLevel, a.SavePassword
              from Account a
              JOIN AccountShare ash ON ash.AccountID = a.AccountID
              JOIN UserAgent ua ON ua.UserAgentID = ash.UserAgentID
              JOIN UserAgent au ON ua.AgentID = au.ClientID and ua.ClientID = au.AgentID
              JOIN Usr u ON u.UserID = ua.AgentID
              WHERE au.IsApproved = 1 AND ua.IsApproved = 1
                and a.UserID = :userid
                and a.ProviderID = :providerid
                and (
                  (ua.AccessLevel = :access_level and a.SavePassword = :save_password)
                  or
                  (ua.AccessLevel < :access_level and a.SavePassword <> :save_password)
                )
                and u.AccountLevel = :agent_account_level
              group by a.AccountID, ua.AccessLevel, a.SavePassword",
            [
                ':userid' => $user->getUserid(),
                ':providerid' => 1, // AA Provider
                ':access_level' => UseragentRepository::ACCESS_WRITE,
                ':save_password' => SAVE_PASSWORD_LOCALLY,
                ':agent_account_level' => ACCOUNT_LEVEL_BUSINESS,
            ]
        )->fetchAll(\PDO::FETCH_ASSOC);

        $accountRep = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class);

        foreach ($accounts as $id => $row) {
            if ($row['AccessLevel'] == UseragentRepository::ACCESS_WRITE && $row['SavePassword'] == SAVE_PASSWORD_LOCALLY) {
                if ($this->localPassManager->hasPassword($row['ID'])) {
                    $account = $accountRep->find($row['ID']);

                    if ($account) {
                        $this->setAccountStorage($account, SAVE_PASSWORD_LOCALLY, SAVE_PASSWORD_DATABASE);
                    }
                }
                unset($accounts[$id]);
            }
        }

        foreach ($accounts as $id => $row) {
            if ($row['AccessLevel'] < UseragentRepository::ACCESS_WRITE && $row['SavePassword'] == SAVE_PASSWORD_DATABASE) {
                $account = $accountRep->find($row['ID']);

                if ($account) {
                    $this->setAccountStorage($account, SAVE_PASSWORD_DATABASE, SAVE_PASSWORD_LOCALLY);
                }
            }
        }
        $this->em->flush();
    }

    public function fetchLogin2Options(Provider $provider, Usr $user): ?array
    {
        $checker = $this->globalVariables->getAccountChecker($provider, true);

        $checkerFields = ['Login2'];
        $checker->setUserFields($user);
        $checker->TuneFormFields($checkerFields, []);

        return $checkerFields['Login2']['Options'] ?? null;
    }
}
