<?php

namespace AwardWallet\Tests\Unit\Business;

use AwardWallet\MainBundle\Command\Business\KeepUpgradedCommand;
use AwardWallet\MainBundle\Entity\Usr;
use AwardWallet\Tests\Unit\BaseUserTest;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 */
class KeepUpgradedCommandTest extends BaseUserTest
{
    /**
     * @var CommandTester
     */
    private $commandTester;

    /**
     * @var Usr
     */
    private $business;

    /**
     * @var ContainerAwareCommand
     */
    private $command;

    /**
     * @var TestHandler
     */
    private $logs;

    public function _before()
    {
        parent::_before();
        $app = new Application($this->container->get('kernel'));
        $app->add($this->container->get(KeepUpgradedCommand::class));

        /** @var KeepUpgradedCommand $command */
        $this->command = $app->find('aw:business:keep-upgraded');
        $this->commandTester = new CommandTester($this->command);

        $userId = $this->aw->createBusinessUserWithBookerInfo('testbus' . $this->aw->grabRandomString(5), [
            'Company' => 'TestBooker',
        ]);
        $this->business = $this->em->getRepository(\AwardWallet\MainBundle\Entity\Usr::class)->find($userId);
        $this->business->getBusinessInfo()->setBalance(0);
        $this->em->flush();

        $this->logs = new TestHandler();
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);
    }

    public function testNoUsers()
    {
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 0, upgraded: 0", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testNotKeepUpgraded()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid());
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 0, upgraded: 0", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testKeepUpgradedEmptyBalance()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, false, ['KeepUpgraded' => 1]);
        $this->business->getBusinessInfo()->setBalance(0);
        $this->em->flush();
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 1, upgraded: 0", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
    }

    public function testKeepUpgradedPositiveBalance()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, false, ['KeepUpgraded' => 1]);
        $this->business->getBusinessInfo()->setBalance(100);
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 1, upgraded: 1", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $this->assertEquals(70, $this->business->getBusinessInfo()->getBalance());
        $this->assertEquals(date("Y-m-d", strtotime("+1 year")), $this->user->getPlusExpirationDate()->format("Y-m-d"));

        $this->runCommand();
        $this->assertStringContainsString("done, processed: 0, upgraded: 0", $this->getLogs());
    }

    public function testKeepUpgradedDiscount100()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, false, ['KeepUpgraded' => 1]);
        $this->business->getBusinessInfo()->setBalance(100);
        $this->business->getBusinessInfo()->setDiscount(100);
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 1, upgraded: 1", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_AWPLUS]);
        $this->assertEquals(70, $this->business->getBusinessInfo()->getBalance());
        $this->assertEquals(date("Y-m-d", strtotime("+1 year")), $this->user->getPlusExpirationDate()->format("Y-m-d"));
    }

    public function testKeepUpgradedDiscountEmptyBalance()
    {
        $this->aw->createConnection($this->user->getUserid(), $this->business->getUserid(), true, true, ['KeepUpgraded' => 1]);
        $this->aw->createConnection($this->business->getUserid(), $this->user->getUserid(), true, false, ['KeepUpgraded' => 1]);
        $this->business->getBusinessInfo()->setBalance(0);
        $this->business->getBusinessInfo()->setDiscount(100);
        $this->runCommand();
        $this->assertStringContainsString("done, processed: 1, upgraded: 0", $this->getLogs());
        $this->db->seeInDatabase('Usr', ['UserID' => $this->user->getUserid(), 'AccountLevel' => ACCOUNT_LEVEL_FREE]);
        $this->assertEquals(0, $this->business->getBusinessInfo()->getBalance());
    }

    public function _after()
    {
        $this->container->get(LoggerInterface::class)->popHandler();

        $this->logs =
        $this->business =
        $this->commandTester =
        $this->command = null;

        parent::_after();
    }

    private function runCommand()
    {
        $this->commandTester->execute([
            'command' => $this->command->getName(),
            '--business' => $this->business->getUserid(),
        ]);
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }
}
