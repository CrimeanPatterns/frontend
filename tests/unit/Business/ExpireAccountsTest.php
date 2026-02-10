<?php

namespace AwardWallet\Tests\Unit\Business;

use AwardWallet\MainBundle\Command\ExpireAwPlusCommand;
use AwardWallet\MainBundle\Entity\Cart;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\DataProvider\ExpireAwPlusDataProvider;
use AwardWallet\MainBundle\FrameworkExtension\Mailer\MailerCollection;
use AwardWallet\MainBundle\Service\Billing\ExpirationCalculator;
use AwardWallet\Tests\Unit\BaseUserTest;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 */
class ExpireAccountsTest extends BaseUserTest
{
    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $commandName;

    /** @var TestHandler */
    private $logs;

    /**
     * @var Usr
     */
    private $business;

    public function _before()
    {
        parent::_before();

        $app = new Application($this->container->get('kernel'));
        $app->add(new ExpireAwPlusCommand(
            $this->container->get(LoggerInterface::class),
            $this->container->get(ExpireAwPlusDataProvider::class),
            $this->container->get(MailerCollection::class)
        ));

        /** @var ExpireAwPlusCommand $command */
        $command = $app->find('aw:email:expire-awplus');
        $this->commandTester = new CommandTester($command);
        $this->commandName = $command->getName();

        $this->logs = new TestHandler();
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);

        $userId = $this->aw->createBusinessUserWithBookerInfo('testbus' . $this->aw->grabRandomString(5), [
            'Company' => 'TestBooker',
        ]);
        $this->aw->addUserPayment($this->user->getId(), Cart::PAYMENTTYPE_APPSTORE, null, [], new \DateTime('-5 year'));
        $this->user->setAccountLevel(ACCOUNT_LEVEL_AWPLUS);
        $this->user->setPlusExpirationDate(new \DateTime('-4 year'));
        $this->business = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
        $this->business->getBusinessInfo()->setBalance(0);
        $this->em->flush();
        $this->em->refresh($this->user);
    }

    public function _after()
    {
        $this->container->get(LoggerInterface::class)->popHandler();
        $this->container->get("monolog.logger.payment")->popHandler();

        $this->business =
        $this->logs =
        $this->commandTester =
        $this->command = null;

        parent::_after();
    }

    public function testNotConnected()
    {
        $this->executeCommand();
        $this->logNotContains('user upgraded by business');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testConnectedNotUpgraded()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid());
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid());
        $this->executeCommand();
        $this->logNotContains('user upgraded by business');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testConnectedZeroBalance()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->executeCommand();
        $this->logNotContains('user upgraded by business');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testConnectedPositiveBalance()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->business->getBusinessInfo()->setBalance(100);
        $this->em->persist($this->business);
        $this->em->flush();

        $this->executeCommand();
        $this->logContains('user upgraded by business');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
    }

    public function testExpiresSoon()
    {
        $expirationDate = date_create('+1 month');
        $payDate = (clone $expirationDate)->modify('-6 month');
        $expectedExpirationDate = (clone $payDate)->modify('+6 month');

        if ($expectedExpirationDate->format('Y-m-d') !== $expirationDate->format('Y-m-d')) {
            $this->markTestSkipped('Current date is not suitable for test');
        }

        $mock = $this->mockServiceWithBuilder(ExpirationCalculator::class);
        $mock->method('getAccountExpiration')->willReturn(['date' => $expirationDate->getTimestamp(), 'lastPrice' => 5]);

        $cart = $this->aw->addUserPayment(
            $this->user->getId(),
            Cart::PAYMENTTYPE_APPSTORE,
            null,
            [],
            $payDate
        );
        $this->db->executeQuery("update Usr set Accounts = 10 where UserID = " . $this->user->getId());
        $this->em->refresh($cart);
        $this->em->refresh($this->user);

        $this->executeCommand();
        $this->logNotContains('user upgraded by business');
        $this->logContains('was noticed about expiration');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getId(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
    }

    public function testExpiresSoonBusiness()
    {
        $ts = time();
        $mock = $this->mockServiceWithBuilder(ExpirationCalculator::class);
        $mock->method('getAccountExpiration')->willReturn(['date' => strtotime("+1 month", $ts), "lastPrice" => 5]);

        $cart = $this->aw->addUserPayment($this->user->getUserid(), Cart::PAYMENTTYPE_APPSTORE,
            null, [], new \DateTime("@" . strtotime("-5 month", $ts)));
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->db->executeQuery("update Usr set Accounts = 10 where UserID = " . $this->user->getUserid());
        $this->em->refresh($cart);
        $this->em->refresh($this->user);

        $this->executeCommand();
        $this->logNotContains('user upgraded by business');
        $this->logNotContains('was noticed about expiration');
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }

    /**
     * @param string $str
     */
    private function logContains($str)
    {
        $this->assertStringContainsString($str, $this->getLogs());
    }

    /**
     * @param string $str
     */
    private function logNotContains($str)
    {
        $this->assertStringNotContainsString($str, $this->getLogs());
    }

    private function executeCommand($userId = null, $notifyExpiresSoon = true)
    {
        $userId = !isset($userId) ? $this->user->getUserid() : $userId;
        $input = [
            'command' => $this->commandName,
            '--userId' => [$userId],
            '--expiresSoon' => $notifyExpiresSoon,
        ];

        $this->commandTester->execute($input);
    }
}
