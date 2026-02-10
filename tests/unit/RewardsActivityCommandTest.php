<?php

namespace AwardWallet\Tests\Unit;

use AwardWallet\MainBundle\Command\RewardsActivityCommand;
use AwardWallet\MainBundle\Entity\Account;
use AwardWallet\MainBundle\Entity\Useragent;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\RewardsActivityProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\Globals\Localizer\LocalizeService;
use Codeception\Module\Aw;
use Codeception\Module\Mail;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * @group frontend-unit
 */
class RewardsActivityCommandTest extends CommandTester
{
    /**
     * @var Mail
     */
    private $mail;

    public function _before()
    {
        parent::_before();

        $this->reInitCommand();

        /** @var Mail mail */
        $this->mail = $this->getModule('Mail');
        $this->setActivity(REWARDS_NOTIFICATION_WEEK);
    }

    public function reInitCommand(): void
    {
        $this->initCommand(new RewardsActivityCommand(
            $this->container->get(LoggerInterface::class),
            $this->container->get(EntityManagerInterface::class)->getConnection(),
            $this->container->get(RewardsActivityProvider::class),
            $this->container->get(MailerCollection::class)
        ));
    }

    public function _after()
    {
        $this->cleanCommand();
        parent::_after();
    }

    /**
     * @dataProvider getPeriods
     */
    public function testNoActivity($startDate, $period)
    {
        $this->runCommand($this->user, $period, $startDate);
        $this->logNotContains("mailing to " . $this->user->getEmail());
    }

    public function getPeriods()
    {
        return [
            [new \DateTime("-6 hour"), 'day'],
            [new \DateTime("-1 day"), 'week'],
            [new \DateTime("-1 day"), 'month'],
        ];
    }

    public function testUserProfileOption()
    {
        $this->addActivity($this->user);

        $this->setActivity(REWARDS_NOTIFICATION_NEVER);
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());

        $this->reInitCommand();
        $this->setActivity(REWARDS_NOTIFICATION_WEEK);
        $this->runCommand($this->user, 'month', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());

        $this->reInitCommand();
        $this->setActivity(REWARDS_NOTIFICATION_MONTH);
        $this->runCommand($this->user, 'day', new \DateTime("-6 hour"));
        $this->logNotContains("mailing to " . $this->user->getEmail());

        $this->reInitCommand();
        $this->setActivity(REWARDS_NOTIFICATION_DAY);
        $this->runCommand($this->user, 'day', new \DateTime("-6 hour"));
        $this->logContains("mailing to " . $this->user->getEmail());

        $localizer = $this->container->get(LocalizeService::class);
        $this->mail->seeEmailTo($this->user->getEmail(), 'Rewards activity (' .
            $localizer->patternDateTime(new \DateTime("-6 hour"), "EEEE, LLL d, yy"));
    }

    public function testUserActivity()
    {
        $account = $this->addActivity($this->user);
        $this->setActivity(REWARDS_NOTIFICATION_WEEK);
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());

        // provider state
        $provider = $account->getProviderid();
        $state = $provider->getState();
        $provider->setState(PROVIDER_IN_DEVELOPMENT);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $provider->setState($state);
        $this->em->flush();

        // account state
        $account->setState(ACCOUNT_DISABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $account->setState(ACCOUNT_ENABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());

        // change count
        $account->setChangecount(0);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $account->setChangecount(1);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());

        // subaccounts
        $this->em->remove($account);
        $this->em->flush();
        $account = $this->addActivity($this->user, null, '2.subaccounts');
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("First subaccount");
        $this->logContains("Second subaccount");
        $this->logContains("mailing to " . $this->user->getEmail());

        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-100 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
    }

    public function testFamilyMemberActivity()
    {
        /** @var Useragent $fm */
        $fm = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)
            ->find($this->aw->createFamilyMember($this->user->getUserid(), 'Billy', 'Villy', null, 'test@mail.com'));
        $account = $this->addActivity($this->user, $fm);
        $fm->setSendemails(true);
        $this->user->setEmailFamilyMemberAlert(false);
        $this->setActivity(REWARDS_NOTIFICATION_WEEK);
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // provider state
        $provider = $account->getProviderid();
        $state = $provider->getState();
        $provider->setState(PROVIDER_IN_DEVELOPMENT);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $provider->setState($state);
        $this->em->flush();

        // account state
        $account->setState(ACCOUNT_DISABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $account->setState(ACCOUNT_ENABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // change count
        $account->setChangecount(0);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $account->setChangecount(1);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // subaccounts
        $this->em->remove($account);
        $this->em->flush();
        $account = $this->addActivity($this->user, $fm, '2.subaccounts');
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("First subaccount");
        $this->logContains("Second subaccount");
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-100 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());

        // empty email
        $fEmail = $fm->getEmail();
        $fm->setEmail(null);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fEmail);
        $fm->setEmail($fEmail);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // uncheck Send emails
        $fm->setSendemails(false);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $fm->setSendemails(true);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // option Email connected
        $this->user->setEmailFamilyMemberAlert(true);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());
        $this->user->setEmailFamilyMemberAlert(false);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // u.Email == ua.Email
        $fm->setEmail($this->user->getEmail());
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($this->user, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $fEmail);
    }

    public function testBusinessActivity()
    {
        $businessId = $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [
            'FirstName' => 'Business',
            'LastName' => 'Account',
            'Company' => 'Test Company',
            'AccountLevel' => ACCOUNT_LEVEL_BUSINESS,
        ], true);
        $business = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($businessId);
        $this->aw->createConnection($business->getUserid(), $this->user->getUserid(), true, true, [
            "AccessLevel" => ACCESS_ADMIN,
        ]);
        $this->aw->createConnection($this->user->getUserid(), $business->getUserid(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
        ]);
        // second admin
        $secondAdmin = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find(
            $this->aw->createAwUser('test' . $this->aw->grabRandomString(5), Aw::DEFAULT_PASSWORD, [
                'FirstName' => 'Second',
                'LastName' => 'Admin',
            ], true)
        );
        $this->aw->createConnection($business->getUserid(), $secondAdmin->getUserid(), true, true, [
            "AccessLevel" => ACCESS_ADMIN,
        ]);
        $this->aw->createConnection($secondAdmin->getUserid(), $business->getUserid(), true, true, [
            "AccessLevel" => ACCESS_WRITE,
        ]);

        /** @var Useragent $fm */
        $fm = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Useragent::class)
            ->find($this->aw->createFamilyMember($business->getUserid(), 'Billy', 'Villy', null, 'test@mail.com'));

        $account = $this->addActivity($business, $fm);
        $fm->setSendemails(true);
        $business->setEmailFamilyMemberAlert(false);
        $this->setActivity(REWARDS_NOTIFICATION_WEEK, $business);
        $this->em->flush();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // provider state
        $provider = $account->getProviderid();
        $state = $provider->getState();
        $provider->setState(PROVIDER_IN_DEVELOPMENT);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $provider->setState($state);
        $this->em->flush();

        // account state
        $account->setState(ACCOUNT_DISABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $account->setState(ACCOUNT_ENABLED);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // change count
        $account->setChangecount(0);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $account->setChangecount(1);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // subaccounts
        $this->em->remove($account);
        $this->em->flush();
        $account = $this->addActivity($business, $fm, '2.subaccounts');
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logContains("First subaccount");
        $this->logContains("Second subaccount");
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-100 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $business->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());

        // empty email
        $fEmail = $fm->getEmail();
        $fm->setEmail(null);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fEmail);
        $fm->setEmail($fEmail);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // uncheck Send emails
        $fm->setSendemails(false);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fm->getEmail());
        $fm->setSendemails(true);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // option Email connected
        $business->setEmailFamilyMemberAlert(true);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());
        $business->setEmailFamilyMemberAlert(false);
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logNotContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logContains("mailing to " . $fm->getEmail());

        // u.Email == ua.Email
        $fm->setEmail($this->user->getEmail());
        $this->em->flush();
        $this->reInitCommand();
        $this->runCommand($business, 'week', new \DateTime("-1 day"));
        $this->logContains("mailing to " . $this->user->getEmail());
        $this->logNotContains("mailing to " . $secondAdmin->getEmail());
        $this->logNotContains("mailing to " . $fEmail);
    }

    /**
     * @return Account
     */
    private function addActivity(Usr $user, ?Useragent $ua = null, $login = 'balance.random')
    {
        $fields = [];

        if (isset($ua)) {
            $fields['UserAgentID'] = $ua->getId();
        }
        $accountId = $this->aw->createAwAccount($user->getId(), 'testprovider', $login, null, $fields);
        $this->aw->checkAccount($accountId);
        sleep(1);
        $this->aw->checkAccount($accountId);

        return $this->em->getRepository(\AwardWallet\MainBundle\Entity\Account::class)->find($accountId);
    }

    private function setActivity($activity, ?Usr $user = null)
    {
        if (!isset($user)) {
            $user = $this->user;
        }
        $user->setEmailrewards($activity);
        $this->em->flush();
    }

    private function runCommand(Usr $user, $period, \DateTime $startDate)
    {
        $this->logs->clear();
        $this->executeCommand([
            'period' => $period,
            '--userId' => [$user->getId()],
            '--startDate' => $startDate->format("Y-m-d H:i:s"),
        ]);
    }
}
