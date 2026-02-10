<?php

namespace AwardWallet\Tests\Unit\BusinessTransaction;

use AwardWallet\MainBundle\Command\BillBusinessCommand;
use Monolog\Handler\TestHandler;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @group frontend-unit
 * @group billing
 */
class BillBusinessCommandTest extends AbstractTest
{
    /** @var CommandTester */
    private $commandTester;

    /** @var string */
    private $commandName;

    /** @var TestHandler */
    private $logs;

    public function _before()
    {
        parent::_before();

        $this->logs = new TestHandler();
        $this->container->get("monolog.logger.payment")->pushHandler($this->logs);
        $this->container->get(LoggerInterface::class)->pushHandler($this->logs);

        $app = new Application($this->container->get('kernel'));
        $app->add($this->container->get(BillBusinessCommand::class));

        /** @var BillBusinessCommand $command */
        $command = $app->find('aw:bill-business');
        $this->commandTester = new CommandTester($command);
        $this->commandName = $command->getName();
    }

    public function _after()
    {
        $this->container->get(LoggerInterface::class)->popHandler();

        $this->logs =
        $this->commandTester = null;

        parent::_after();
    }

    public function testTrial()
    {
        $this->business->getBusinessInfo()->setTrialEndDate(new \DateTime("+12 month"));
        $this->runCommand();
        $this->assertStringContainsString('done, businesses: 1, members: 0', $this->getLogs());
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'Balance' => 0,
        ]);
    }

    public function testOneMember()
    {
        $this->info->setBalance(10);
        $this->em->flush();
        $this->runCommand();
        $this->assertStringContainsString('done, businesses: 1, members: 1', $this->getLogs());
        $this->db->seeInDatabase('BusinessInfo', [
            'UserID' => $this->business->getUserid(),
            'Balance' => 6,
        ]);
    }

    public function testOneMemberAndBalanceIsNotEnough()
    {
        $this->info->setBalance(0.2);
        $this->em->flush();
        $this->runCommand();
        $this->assertStringContainsString('insufficient balance', $this->getLogs());
        $this->assertStringContainsString('businesses: 1, members: 0', $this->getLogs());
    }

    private function getLogs()
    {
        return "\n------\n" . implode("\n", array_map(function (array $record) {
            return $record['message'];
        }, $this->logs->getRecords())) . "\n------\n";
    }

    private function runCommand()
    {
        $this->commandTester->execute([
            'command' => $this->commandName,
            'UserID' => $this->business->getUserid(),
        ]);
    }
}
