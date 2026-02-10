<?php

namespace AwardWallet\Tests\Unit\MainBundle\Loyalty\Listener;

use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\BalanceWatch;
use AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchRefund;
use AwardWallet\MainBundle\Entity\BusinessTransaction\BalanceWatchStart;
use AwardWallet\MainBundle\Entity\CartItem\BalanceWatchCredit;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\Event\AccountBalanceChangedEvent;
use AwardWallet\MainBundle\Event\AccountUpdatedEvent;
use AwardWallet\MainBundle\Form\Model\AccountModel;
use AwardWallet\MainBundle\FrameworkExtension\AwTokenStorageInterface;
use AwardWallet\MainBundle\Globals\StringUtils;
use AwardWallet\MainBundle\Loyalty\AccountSaving\ProcessingReport;
use AwardWallet\MainBundle\Loyalty\AccountSaving\Processors\BalanceProcessor;
use AwardWallet\MainBundle\Loyalty\Resources\CheckAccountResponse;
use AwardWallet\MainBundle\Loyalty\Resources\UserData;
use AwardWallet\MainBundle\Service\BackgroundCheckScheduler;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchListener;
use AwardWallet\MainBundle\Service\BalanceWatch\BalanceWatchManager;
use AwardWallet\MainBundle\Service\BalanceWatch\Constants;
use AwardWallet\MainBundle\Service\BalanceWatch\Notifications;
use AwardWallet\MainBundle\Service\BalanceWatch\Query;
use AwardWallet\MainBundle\Service\BalanceWatch\Stopper;
use AwardWallet\MainBundle\Service\BusinessTransaction\BalanceWatchProcessor;
use AwardWallet\Tests\Unit\BaseContainerTest;
use Codeception\Module\Mail;
use Psr\Log\NullLogger;

/**
 * @group frontend-unit
 */
class BalanceWatchListenerTest extends BaseContainerTest
{
    private ?Account $account;

    private ?BalanceWatchListener $balanceWatchListner;

    private ?BalanceWatchManager $balanceWatchManager;

    private ?BalanceProcessor $balanceProcessor;

    private ?Mail $mail;

    private ?Usr $user;

    public function _before()
    {
        parent::_before();

        $providerId = $this->aw->createAwProvider('testProvider' . StringUtils::getRandomCode(8), StringUtils::getRandomCode(8));
        $userId = $this->aw->createAwUser($login = 'login' . StringUtils::getRandomCode(8), null, [
            'BalanceWatchCredits' => 2,
        ]);

        $container = $this->getModule('Symfony')->_getContainer();
        $provider = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Provider::class)->findOneBy(['providerid' => $providerId]);
        $this->user = $container->get('doctrine')->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->findOneBy(['userid' => $userId]);
        $this->container->get('aw.manager.user_manager')->loadToken($this->user, false);

        $this->account = (new Account())
            ->setUser($this->user)
            ->setBalance(0)
            ->setErrorcode(ACCOUNT_CHECKED)
            ->setLastbalance(0)
            ->setUpdatedate(new \DateTime('-30 minute'))
            ->setLastchangedate(new \DateTime('-30 minute'))
            ->setProviderid($provider)
            ->setLogin('loginfakeuser')
            ->setBalanceWatchStartDate(new \DateTime('-3 hour'));

        $this->em->persist($this->account);
        $this->em->flush();

        $this->mail = $this->getModule('Mail');

        $this->balanceWatchManager = $this->getBalanceWatchManager();
        $this->balanceProcessor = $this->container->get(BalanceProcessor::class);
        $this->balanceWatchListner = new BalanceWatchListener(
            new NullLogger(),
            $this->em,
            $this->container->get(Query::class),
            $this->container->get(Stopper::class)
        );
    }

    public function _after()
    {
        $this->em->remove($this->user);
        $this->em->remove($this->account);
        $this->em->flush();
        $this->account = null;
        $this->balanceWatchListner = null;
        $this->balanceWatchManager = null;
        $this->balanceProcessor = null;

        parent::_after();
    }

    public function testBalanceChanged()
    {
        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $randBalance = \random_int(0, 10000);
        0 === $randBalance ? ++$randBalance : null;

        $this->balanceProcessor->saveAccountBalance($this->account, $randBalance);
        $this->em->persist($this->account);
        $this->db->haveInDatabase('AccountBalance', [
            'AccountID' => $this->account->getAccountid(),
            'Balance' => $randBalance,
            'UpdateDate' => date('Y-m-d H:i:s'),
        ]);

        $this->account->setBalanceWatchStartDate(null);
        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setExpectedPoints(null)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);
        $this->account->setBalanceWatchStartDate(new \DateTime('-1 hour'));

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_CHECKED, "Success", $userData, new ProcessingReport()));
        $this->balanceWatchListner->onAccountBalanceChanged(
            new AccountBalanceChangedEvent($this->account)
        );
        $this->mail->seeEmailTo($this->user->getEmail(), 'change was detected', 'This account was reverted to a normal background updating schedule');
        $this->checkTransVars($this->mail->grabLastMail()->getBody());

        $accountRow = $this->em->getConnection()->executeQuery('SELECT UpdateDate, Balance, BalanceWatchStartDate FROM Account WHERE AccountID = ?', [$this->account->getAccountid()])->fetch(\PDO::FETCH_ASSOC);
        $this->assertEquals($randBalance, $accountRow['Balance']);
        $this->isNull($accountRow['BalanceWatchStartDate']);
    }

    /*public function testTimeout()
    {
        $this->account->setBalanceWatchStartDate(new \DateTime('-1 week'));
        $this->em->persist($this->account);
        $this->em->flush();

        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $response = (new CheckAccountResponse)
            ->setState(ACCOUNT_CHECKED)
            ->setBalance(0)
            ->setUserdata($userData);

        $this->balanceWatchListner->onLoyaltySaveAccountResponse(new LoyaltySaveAccountResponseEvent($this->account, $response));
        $this->mail->seeEmailTo($this->user->getEmail(), 'has been reverted to a normal background updating schedule', 'we did not detect any changes to this account');
        $this->checkTransVars($this->mail->grabLastMail()->getBody());

        $accountRow = $this->em->getConnection()->executeQuery('SELECT UpdateDate, Balance, BalanceWatchStartDate FROM Account WHERE AccountID = ?', [$this->account->getAccountid()])->fetch(\PDO::FETCH_ASSOC);
        $this->isNull($accountRow['BalanceWatchStartDate']);
    }*/

    public function testUpdateError()
    {
        $creditsCountBeforeStart = (int) $this->db->grabFromDatabase('Usr', 'BalanceWatchCredits', ['UserID' => $this->user->getUserid()]);
        $this->account->setBalanceWatchStartDate(null);

        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);

        $this->assertEquals($creditsCountBeforeStart - Constants::TRANSACTION_COST, $this->user->getBalanceWatchCredits());
        $this->mail->seeEmailTo($this->user->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $this->checkTransVars($this->mail->grabLastMail()->getBody());

        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_MISSING_PASSWORD, "Missing password", $userData, new ProcessingReport()));
        $this->mail->seeEmailTo($this->user->getEmail(), 'is returning an error', 'we received an error while updating this account');
        $this->assertEquals($creditsCountBeforeStart, $this->user->getBalanceWatchCredits());
    }

    public function testAccountWithExistError()
    {
        $container = $this->container;

        $creditsCountBeforeStart = (int) $this->db->grabFromDatabase('Usr', 'BalanceWatchCredits', ['UserID' => $this->user->getUserid()]);
        $this->account->setBalanceWatchStartDate(null);
        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);

        $this->assertEquals($creditsCountBeforeStart - Constants::TRANSACTION_COST, $this->user->getBalanceWatchCredits());
        $this->mail->seeEmailTo($this->user->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $this->checkTransVars($this->mail->grabLastMail()->getBody());

        $this->account->setErrorcode(ACCOUNT_WARNING);
        $this->account->setErrormessage('field ErrorMessage account');
        $container->get('doctrine.orm.default_entity_manager')->persist($this->account);
        $container->get('doctrine.orm.default_entity_manager')->flush();

        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_LOCKOUT, "Lockoout", $userData, new ProcessingReport()));
        $this->mail->seeEmailTo($this->user->getEmail(), 'is returning an error', 'we received an error while updating this account');
        $this->assertEquals($creditsCountBeforeStart, $this->user->getBalanceWatchCredits());
    }

    public function testExpectedPointAccumulationAccountBalance(bool $required = true)
    {
        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $balance = 600;
        $balanceStep = 100;
        $expectedPoint = ($balanceStep * 3) + ($required ? 0 : 1);

        $this->db->haveInDatabase('AccountBalance', [
            'AccountID' => $this->account->getAccountid(),
            'Balance' => $balance,
            'UpdateDate' => date('Y-m-d H:i:s', time() - 60),
        ]);

        $this->account
            ->setBalance($balance)
            ->setLastbalance($balance - $balanceStep)
            ->setBalanceWatchStartDate(null);
        $this->em->persist($this->account);
        $this->em->flush();

        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setExpectedPoints($expectedPoint)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);

        foreach ([10, 20, 30] as $dateStep) {
            $this->db->haveInDatabase('AccountBalance', [
                'AccountID' => $this->account->getAccountid(),
                'Balance' => ($balance += $balanceStep),
                'UpdateDate' => date('Y-m-d H:i:s', time() + $dateStep),
            ]);
        }

        $this->account
            ->setBalance($balance)
            ->setLastbalance($balance - $balanceStep)
            ->setLastchangedate(new \DateTime('@' . (time() + 100)))
            ->setBalanceWatchStartDate(new \DateTime());

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_CHECKED, 'Success', $userData, new ProcessingReport()));
        $this->balanceWatchListner->onAccountBalanceChanged(
            new AccountBalanceChangedEvent($this->account)
        );

        $required
            ? $this->mail->seeEmailTo($this->user->getEmail(), 'change was detected', 'This account was reverted to a normal background updating schedule')
            : $this->mail->dontSeeEmailTo($this->user->getEmail(), 'change was detected', 'This account was reverted to a normal background updating schedule');

        $accountRow = $this->em->getConnection()->executeQuery('SELECT UpdateDate, Balance, BalanceWatchStartDate FROM Account WHERE AccountID = ?', [$this->account->getAccountid()])->fetch(\PDO::FETCH_ASSOC);
        $required
            ? $this->assertNull($accountRow['BalanceWatchStartDate'])
            : $this->assertNotNull($accountRow['BalanceWatchStartDate']);
    }

    public function testExpectedPointsTooSmall()
    {
        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $balance = 100;

        $this->em->persist($this->account);
        $this->db->haveInDatabase('AccountBalance', [
            'AccountID' => $this->account->getAccountid(),
            'Balance' => $balance,
            'UpdateDate' => date('Y-m-d H:i:s'),
        ]);

        $this->account->setBalanceWatchStartDate(null);
        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setExpectedPoints(1000)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);
        $this->account->setBalanceWatchStartDate(new \DateTime('-1 hour'));

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_CHECKED, "Success", $userData, new ProcessingReport()));
        $this->mail->dontSeeEmailTo($this->user->getEmail(), 'change was detected', 'This account was reverted to a normal background updating schedule');

        $accountRow = $this->em->getConnection()->executeQuery('SELECT UpdateDate, Balance, BalanceWatchStartDate FROM Account WHERE AccountID = ?', [$this->account->getAccountid()])->fetch(\PDO::FETCH_ASSOC);
        $this->assertNotNull($accountRow['BalanceWatchStartDate']);
    }

    public function testExpectedPointsBalance()
    {
        $userData = new UserData();
        $userData->setAccountId($this->account->getAccountid());

        $expectedPoint = 1000;
        $balance = 100;
        $this->balanceProcessor->saveAccountBalance($this->account, $balance);

        $this->em->persist($this->account);
        $this->db->haveInDatabase('AccountBalance', [
            'AccountID' => $this->account->getAccountid(),
            'Balance' => $expectedPoint,
            'UpdateDate' => date('Y-m-d H:i:s', time() + 60 * 60 * 2),
        ]);

        $this->account->setBalanceWatchStartDate(null);
        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setExpectedPoints($expectedPoint)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager()->startBalanceWatch($this->account, $accountModel);
        $this->account->setBalanceWatchStartDate(new \DateTime('-1 hour'));

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($this->account, ACCOUNT_CHECKED, "Success", $userData, new ProcessingReport()));
        $this->balanceWatchListner->onAccountBalanceChanged(
            new AccountBalanceChangedEvent($this->account)
        );
        $this->mail->seeEmailTo($this->user->getEmail(), 'change was detected', 'This account was reverted to a normal background updating schedule');
    }

    public function testBusinessRefund()
    {
        $container = $this->getModule('Symfony')->_getContainer();
        $userRepository = $container->get('doctrine')->getRepository(Usr::class);

        $business = $userRepository->find($this->aw->createBusinessUserWithBookerInfo());
        $admin = $userRepository->find($this->aw->createStaffUserForBusinessUser($business->getUserid()));
        $this->db->haveInDatabase('GroupUserLink', ['SiteGroupID' => 49, 'UserID' => $admin->getUserid()]);

        $admin->setBusinessInfo($business->getBusinessInfo());
        $accountId = $this->aw->createAwAccount($business->getUserid(), 'testprovider', StringUtils::getRandomCode(8), 'test', [
            'ErrorCode' => ACCOUNT_CHECKED,
            'Balance' => 0,
        ]);

        $account = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
        $businessBalance = $business->getBusinessInfo()->getBalance();

        $accountModel = (new AccountModel())
            ->setPointsSource(BalanceWatch::POINTS_SOURCE_PURCHASE)
            ->setTransferRequestDate(new \DateTime('-1 hour'));
        $this->getBalanceWatchManager($admin, $business)->startBalanceWatch($account, $accountModel);

        $this->db->seeInDatabase('BalanceWatch', [
            'AccountID' => $account->getId(),
            'PayerUserID' => $admin->getUserid(),
            'IsBusiness' => 1,
        ]);

        $this->db->seeInDatabase('BusinessTransaction', [
            'UserID' => $business->getUserid(),
            'Type' => BalanceWatchStart::TYPE,
            'Amount' => BalanceWatchCredit::PRICE,
            'Balance' => $businessBalance - BalanceWatchCredit::PRICE,
        ]);
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $business->getUserid(),
            'Balance' => $businessBalance - BalanceWatchCredit::PRICE,
        ]);

        $this->mail->seeEmailTo($admin->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $this->mail->dontSeeEmailTo($business->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $this->mail->dontSeeEmailTo($account->getUser()->getEmail(), 'being monitored for changes', 'is being monitored for changes');
        $this->checkTransVars($this->mail->grabLastMail()->getBody());

        $userData = new UserData();
        $userData->setAccountId($account->getAccountid());

        $this->balanceWatchListner->onAccountUpdated($this->buildAccountUpdatedEvent($account, ACCOUNT_MISSING_PASSWORD, 'Missing password', $userData, new ProcessingReport()));
        $this->mail->seeEmailTo($admin->getEmail(), 'is returning an error', 'we received an error while updating this account');
        $this->mail->dontSeeEmailTo($business->getEmail(), 'is returning an error', 'we received an error while updating this account');
        $this->mail->dontSeeEmailTo($account->getUser()->getEmail(), 'is returning an error', 'we received an error while updating this account');

        $this->db->seeInDatabase('BusinessTransaction', [
            'UserID' => $business->getUserid(),
            'Type' => BalanceWatchRefund::TYPE,
            'Amount' => BalanceWatchCredit::PRICE,
            'Balance' => $businessBalance,
        ]);
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $business->getUserid(),
            'Balance' => $businessBalance,
        ]);
    }

    private function buildAccountUpdatedEvent(Account $account, int $errorCode, string $errorMessage, UserData $userData, ProcessingReport $saveReport)
    {
        return new AccountUpdatedEvent(
            $account,
            (new CheckAccountResponse())
                ->setState($errorCode)
                ->setMessage($errorMessage)
                ->setUserdata($userData),
            new ProcessingReport(),
            AccountUpdatedEvent::UPDATE_METHOD_LOYALTY
        );
    }

    private function checkTransVars($emailBody)
    {
        foreach (['balance', 'provider', 'account'] as $var) {
            $this->assertStringNotContainsString('%' . $var, $emailBody);
        }
    }

    private function getBalanceWatchManager($user = null, $business = null): BalanceWatchManager
    {
        $tokenStorageMock = $this->createMock(AwTokenStorageInterface::class);
        $tokenStorageMock->method('getUser')->willReturn($user ?? $this->user);
        $tokenStorageMock->method('getBusinessUser')->willReturn($business ?? $this->user);

        $container = $this->getModule('Symfony')->_getContainer();

        return new BalanceWatchManager(
            $container->get('monolog.logger.payment'),
            $container->get('doctrine.orm.default_entity_manager'),
            $tokenStorageMock,
            $container->get('security.authorization_checker'),
            $container->get('translator'),
            $container->get(BackgroundCheckScheduler::class),
            $container->get('aw.manager.cart'),
            $container->get(BalanceWatchProcessor::class),
            $container->get(Query::class),
            $container->get(Notifications::class),
            $container->get(Stopper::class)
        );
    }
}
